<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath;

use DOMNode;
use DOMXPath;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\Expression\ExpressionParserHelper;
use RuntimeException;

class XPathPathNode extends AbstractXPath
{
    const LIMIT_BY_ELEMENT = 1;
    const LIMIT_BY_TEXT = 2;
    const LIMIT_ALL = 3;

    /**
     * @var string
     */
    protected $node;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var ExpressionInterface
     */
    protected $selector;

    /**
     * {@inheritdoc}
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    public function parse($string)
    {
        $factory = new Factory();
        $expressionParserHelper = new ExpressionParserHelper;

        if (mb_strpos($string, '[') === false) {
            $this->setNode($string);

            return;
        }

        $pieces = $expressionParserHelper->parseFirstLevelSubExpressions($string, '[', ']');
        $this->setNode(array_shift($pieces));

        // Remove the last empty part
        array_pop($pieces);

        foreach ($pieces as $piece) {
            if ($piece == '') {
                continue;
            }
            
            if (is_numeric($piece)) {
                $this->setPosition($piece);
                continue;
            }

            $this->setSelector($factory->create($piece));
        }
    }

    public function toString()
    {
        return $this->getNode()
            . ($this->getSelector() !== null ? '[' . $this->getSelector()->toString() . ']' : '')
            . ($this->getPosition() !== null ? '[' . $this->getPosition() . ']' : '');
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        if (
            trim($this->getNode()) == ''
            || strpos(trim($this->getNode()), '*') === 0
            || strpos(trim($this->getNode()), '.') === 0
        ) {
            return;
        }

        $parts = explode(':', $this->getNode());

        if (count($parts) == 1) {
            $toSet = $prefix . ':' . $parts[0];
        } else {
            $toSet = $this->getNode();
        }

        $this->setNode($toSet);
    }

    public function setVariableValues(array $values)
    {
        if (!is_null($this->getSelector())) {
            $this->getSelector()->setVariableValues($values);
        }
    }

    public function evaluate($context, DOMXPath $xPathReference)
    {
        return $this->query($context, $xPathReference);
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param string $node
     */
    public function setNode($node)
    {
        $this->node = $node;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getSelector()
    {
        return $this->selector;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setSelector(ExpressionInterface $selector)
    {
        $this->selector = $selector;
    }

    public function query($context)
    {
        // Direct cases
        if ($this->getNode() == '.') {
            return $context;
        }

        if ($this->getNode() == '..') {
            if ($context instanceof DOMNodeList) {
                $context = $context->item(0);
            }

            return new DOMNodeList($context->parentNode);
        }

        if (!$this->getNode()) {
            if ($context instanceof DOMNodeList || $context instanceof \DOMNodeList) {
                $item = $context->item(0);
            } else {
                $item = $context;
            }

            if ($item instanceof \DOMDocument) {
                $doc = $item;
            } else {
                $doc = $item->ownerDocument;
            }

            return $doc;
        }

        $limitBy = static::LIMIT_BY_ELEMENT;
        $nodeName = $this->getNode();

        // Check if is a pseudo
        if (strpos($this->getNode(), '::') !== false) {
            list($pseudoFirst, $pseudoSecond) = explode('::', $this->getNode());

            switch($pseudoFirst) {
                case 'child':
                    switch ($pseudoSecond) {
                        case '*' :
                            $limitBy = static::LIMIT_BY_ELEMENT;
                            $nodeName = '*';
                            break;

                        default:
                            throw new \RuntimeException('Second parameter of child:: not recognised');

                    }
                    break;

                case 'following-sibling':
                    switch ($pseudoSecond) {
                        case '*' :
                            if ($context instanceof DOMNodeList) {
                                if ($context->count() > 1 || $context->count() < 1) {
                                    throw new \RuntimeException('following-sibling only needs 1 context node');
                                }

                                $context = $context->item(0);
                            }

                            $correct = false;

                            while (!$correct && $context->nextSibling !== null) {
                                $context = $context->nextSibling;
                                $correct = $this->getSelector()->evaluate($context, func_get_arg(1));
                            }

                            if ($correct) {
                                return new DOMNodeList($context);
                            }

                            return new DOMNodeList();

                        default:
                            throw new \RuntimeException('Second parameter of following-sibling:: not recognised');

                    }
                    break;

                default:
                    throw new \RuntimeException('Pseudoelement not recognised');
            }
        }

        $parts = explode(':', $nodeName);

        if (count($parts) > 1) {
            list($namespacePrefix, $localName) = $parts;
        } else {
            $localName = $nodeName;
            $namespacePrefix = null;
        }

        // Detect the nodes we are interested in
        $result = new DOMNodeList();

        if (
            $context instanceof \DOMElement
            || $context instanceof \DOMDocument
        ) {
            $contextChilds = new DOMNodeList($context->childNodes);
        } elseif ($context instanceof DOMNodeList && $context->isParent()) {
            $contextChilds = $context;
        } else {
            $contextChilds = new DOMNodeList;

            foreach ($context as $contextElement) {
                $contextChilds->merge(new DOMNodeList($contextElement->childNodes));
            }
        }

        $i = 1;

        foreach ($contextChilds as $childNode) {
            /* @var $childNode DOMNode */
            if (
                (
                    $limitBy === static::LIMIT_BY_ELEMENT && $childNode instanceof \DOMElement
                )
                && (
                    (
                        $childNode->localName === $localName
                        && $childNode->namespaceURI == $this->getNamespaces()[$namespacePrefix]
                    ) || $localName === '*'
                )
                && (
                    !$this->getSelector()
                    || (
                        ($evaluateResult = $this->getSelector()->evaluate($childNode, func_get_arg(1)))
                        && (
                            !$evaluateResult instanceof DOMNodeList
                            || $evaluateResult instanceof DOMNodeList && $evaluateResult->count()
                        )
                    )
                )
            ) {
                $result[] = $childNode;

                if ($this->getPosition() == $i) {
                    break;
                }

                $i++;
            }
        }

        return $result;
    }

    public function setNamespaces(array $namespaces)
    {
        parent::setNamespaces($namespaces);

        if (!is_null($this->getSelector())) {
            $this->getSelector()->setNamespaces($namespaces);
        }
    }

}
