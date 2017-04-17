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
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

class XPathAttributeValueTemplate extends AbstractXPath
{
    protected $parts;

    public static function parseXPath($string)
    {
        $factory = new Factory();
        $total = count($string);

        for ($i = 1; $i < $total; $i += 2) {
            $string[$i] = $factory->create($string[$i]);
        }

        $obj = new self;
        $obj->setParts($string);

        return $obj;
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
                } elseif ($tmp instanceof DOMResultTree) {
                    $tmp = $tmp->evaluate();
                }

                $result .= $tmp;
            } else {
                throw new \RuntimeException('Part not compatible');
            }
        }

        return $result;
    }

    /**
     * @return mixed[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @param mixed[] $parts
     */
    public function setParts($parts)
    {
        $this->parts = $parts;
    }

    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);

        foreach ($this->getParts() as $part) {
            if ($part instanceof ExpressionInterface) {
                $part->setGlobalContext($context);
            }
        }
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        foreach ($this->getParts() as $part) {
            if ($part instanceof ExpressionInterface) {
                $part->setTemplateContext($context);
            }
        }
    }
}
