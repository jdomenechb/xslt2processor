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
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XPath\Expression\ExpressionParserHelper;

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
        $expressionParserHelper = new ExpressionParserHelper();

        if (mb_strpos($string, '[') === false) {
            $this->setNode($string);

            return;
        }

        $pieces = $expressionParserHelper->parseFirstLevelSubExpressions($string, '[', ']');
        $this->setNode(array_shift($pieces));
    }

    public function toString()
    {
        return $this->getNode();
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

    public function evaluate($context)
    {
        return $this->query($context);
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

    public function query($context)
    {
        // Direct cases
        if ($this->getNode() === '.') {
            if (!$context instanceof DOMNodeList) {
                return new DOMNodeList($context);
            }

            return $context;
        }

        if ($this->getNode() === '..') {
            if ($context instanceof DOMNodeList) {
                $context = $context->item(0);
            }

            return new DOMNodeList(($context->parentNode ?: null));
        }

        if (!$this->getNode()) {
            if (
                $context instanceof DOMNodeList
                || $context instanceof \DOMNodeList
            ) {
                $item = $context->item(0);
            } else {
                $item = $context;
            }

            if ($item instanceof \DOMDocument) {
                $doc = $item;
            } else {
                $doc = $item->ownerDocument;
            }

            return new DOMNodeList($doc);
        }

        $limitBy = static::LIMIT_BY_ELEMENT;
        $nodeName = $this->getNode();
        $parts = explode(':', $nodeName);

        if (count($parts) > 1) {
            list($namespacePrefix, $localName) = $parts;
        } else {
            $localName = $nodeName;
            $namespacePrefix = $this->getGlobalContext()->getDefaultNamespace();
        }

        if (!$this->getGlobalContext()->getNamespaces()->offsetExists($namespacePrefix)) {
            throw new \RuntimeException('Namespace with prefix "' . $namespacePrefix . '" is not defined in context');
        }

        // Detect the nodes we are interested in
        $result = new DOMNodeList();

        if (
            $context instanceof \DOMElement
            || $context instanceof \DOMDocument
        ) {
            $contextChilds = new DOMNodeList($context->childNodes);
        } elseif (($context instanceof DOMNodeList && $context->isParent())) {
            $contextChilds = $context;
        } elseif ($context instanceof DOMResultTree) {
            $contextChilds = new DOMNodeList($context->getBaseNode()->childNodes);
        } else {
            $contextChilds = new DOMNodeList();

            foreach ($context as $contextElement) {
                $contextChilds->merge(new DOMNodeList($contextElement instanceof \DOMDocument ? $contextElement->documentElement : $contextElement->childNodes));
            }
        }

        foreach ($contextChilds as $childNode) {
            /* @var $childNode DOMNode */
            if (
                (
                    $limitBy === static::LIMIT_BY_ELEMENT && $childNode instanceof \DOMElement
                )
                && (
                    (
                        $childNode->localName === $localName
                        && $childNode->namespaceURI == $this->getGlobalContext()->getNamespaces()[$namespacePrefix]
                    ) || $localName === '*'
                )
            ) {
                $result[] = $childNode;
            }
        }

        return $result;
    }
}
