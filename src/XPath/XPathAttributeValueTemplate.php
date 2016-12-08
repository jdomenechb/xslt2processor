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

class XPathAttributeValueTemplate implements ExpressionInterface
{
    protected $parts;

    public function __construct($parts)
    {
        $this->parse($parts);
    }

    public function parse($string)
    {
        $factory = new Factory();

        for ($i = 1; $i < count($string); $i = $i + 2) {
            $string[$i] = $factory->create($string[$i]);
        }

        //trigger_error('bleh');

        $this->setParts($string);
    }

    public function toString()
    {
        $result = '';

        foreach ($this->getParts() as $part) {
            if (is_string($part)) {
                $result .= $part;
            } elseif ($part instanceof ExpressionInterface) {
                $result .= '{' . $part->toString() . '}';
            } else {
                throw new \RuntimeException('Part not compatible');
            }
        }

        return $result;
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        foreach ($this->getParts() as $part) {
            if (!$part instanceof ExpressionInterface) {
                continue;
            }

            $part->setDefaultNamespacePrefix($prefix);
        }
    }

    public function setVariableValues(array $values)
    {
        foreach ($this->getParts() as $part) {
            if (!$part instanceof ExpressionInterface) {
                continue;
            }

            $part->setVariableValues($values);
        }
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        $result = '';

        foreach ($this->getParts() as $part) {
            if (is_string($part)) {
                $result .= $part;
            } elseif ($part instanceof ExpressionInterface) {
                $result .= $part->evaluate($context, $xPathReference);
            } else {
                throw new \RuntimeException('Part not compatible');
            }
        }

        return $result;
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
