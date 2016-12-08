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

class XPathPath extends AbstractXPath
{
    /**
     * @var string
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
        $parts = explode('/', $string);
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
     * @param \DOMElement $node
     *
     * @return self
     */
    public static function createFromRelativeNode(\DOMElement $node)
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

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        $xPath = $this->toString();
        $value = $xPathReference->evaluate($xPath, $context);

        // Fix for text of no existing node
        if (
            strrpos($xPath, 'text()', strlen($xPath) - 1 - strlen('text()'))
            && is_object($value)
            && $value instanceof \DOMNodeList
            && $value->length === 0
        ) {
            $value = '';
        }

        return $value;
    }

    /**
     * @return mixed
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
}
