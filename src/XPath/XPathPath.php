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

use DOMElement;
use DOMNodeList as OriginalDOMNodeList;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

class XPathPath extends AbstractXPath
{
    /**
     * @var ExpressionInterface[]
     */
    protected $parts;

    /**
     * {@inheritdoc}
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    public function parse($string)
    {
        $eph = new Expression\ExpressionParserHelper();
        $parts = $eph->explodeRootLevel('/', $string);
        $factory = new Factory();

        foreach ($parts as &$part) {
            $part = $factory->create($part);
        }

        $this->setParts($parts);
    }

    public function toString()
    {
        $parts = $this->getParts();

        foreach ($parts as &$part) {
            $part = $part->toString();
        }

        return implode('/', $parts);
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        foreach ($this->getParts() as $part) {
            $part->setDefaultNamespacePrefix($prefix);
        }
    }

    public function setVariableValues(array $values)
    {
        foreach ($this->getParts() as $part) {
            $part->setVariableValues($values);
        }
    }

    /**
     * @param DOMElement $node
     *
     * @return self
     */
    public static function createFromRelativeNode(DOMElement $node)
    {
        $xPath = $node->nodeName;

        if ($node->attributes->length) {
            $xPath .= '[';

            $attrs = [];

            foreach ($node->attributes as $attribute) {
                $attrs[] = '@' . $attribute->nodeName . '="' . $attribute->nodeValue . '""';
            }

            sort($attrs);

            $xPath .= implode(' and ', $attrs);

            $xPath .= ']';
        }

        $c = get_called_class();

        return new $c($xPath);
    }

    public function evaluate($context)
    {
        $xPath = $this->toString();

        // Evaluate the path
        $evaluation = $context;

        foreach ($this->getParts() as $part) {
            if ($evaluation instanceof DOMNodeList || $evaluation instanceof \DOMNodeList) {
                if ($evaluation instanceof \DOMNodeList) {
                    $evaluation = new DOMNodeList($evaluation);
                }

                $newEvaluation = new DOMNodeList();
                $newEvaluation->merge($part->evaluate($evaluation));

                $evaluation = $newEvaluation;
            } else {
                $evaluation = $part->evaluate($evaluation);
            }
        }

        $value = $evaluation;

        // Fix for text of no existing node
        if (
            is_object($value)
            && ($offset = mb_strlen($xPath) - 1 - mb_strlen('text()')) >= 0
            && strrpos($xPath, 'text()', strlen($xPath) - 1 - strlen('text()'))
            && $value instanceof OriginalDOMNodeList
            && $value->length === 0
        ) {
            $value = '';
        }

        return $value;
    }

    public function query($context)
    {
        $contextList = new DOMNodeList($context);

        foreach ($this->getParts() as $part) {
            $result = new DOMNodeList();

            foreach ($contextList as $contextElement) {
                $newResult = new DOMNodeList($part->query($contextElement));
                $result->merge($newResult);
            }

            $contextList = $result;
        }

        return $contextList;
    }

    /**
     * @return ExpressionInterface[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @param mixed $parts
     */
    public function setParts($parts)
    {
        $this->parts = $parts;
    }

    public function setNamespaces(array $namespaces)
    {
        parent::setNamespaces($namespaces);

        foreach ($this->getParts() as $part) {
            $part->setNamespaces($this->getNamespaces());
        }
    }

    public function setKeys(array $keys)
    {
        foreach ($this->getParts() as $part) {
            $part->setKeys($keys);
        }
    }
}
