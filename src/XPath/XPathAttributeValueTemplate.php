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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

class XPathAttributeValueTemplate extends AbstractXPath
{
    protected $parts;

    public function __construct($parts)
    {
        $this->parse($parts);
    }

    public function parse($string)
    {
        $factory = new Factory();
        $total = count($string);

        for ($i = 1; $i < $total; $i += 2) {
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

    public function evaluate($context)
    {
        $result = '';

        foreach ($this->getParts() as $part) {
            if (is_string($part)) {
                $result .= $part;
            } elseif ($part instanceof ExpressionInterface) {
                $tmp = $part->evaluate($context);

                if ($tmp instanceof DOMNodeList) {
                    $tmp = $tmp->item(0)->nodeValue;
                }

                $result .= $tmp;
            } else {
                throw new \RuntimeException('Part not compatible');
            }
        }

        return $result;
    }

    public function setNamespaces(array $namespaces)
    {
        parent::setNamespaces($namespaces);

        foreach ($this->getParts() as $part) {
            if (!$part instanceof ExpressionInterface) {
                continue;
            }

            $part->setNamespaces($namespaces);
        }
    }

    public function setKeys(array $keys)
    {
        foreach ($this->getParts() as $part) {
            if (!$part instanceof ExpressionInterface) {
                continue;
            }

            $part->setKeys($keys);
        }
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
