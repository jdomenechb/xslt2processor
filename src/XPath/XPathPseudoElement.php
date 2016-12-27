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

class XPathPseudoElement extends AbstractXPath
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $node;

    public function parse($string)
    {
        if (strpos($string, '::') === false) {
            return false;
        }

        $parts = explode('::', $string);

        $this->setName($parts[0]);
        $this->setNode($parts[1]);

        return true;
    }


    public function toString()
    {
        return $this->getName() . '::' . $this->getNode();
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
        switch ($this->getName()) {
            case 'child':
                switch ($this->getNode()) {
                    case '*':
                        $result = new DOMNodeList();

                        foreach ($context as $node) {
                            $result->merge(new DOMNodeList($node->childNodes));
                        }

                        return $result;

                    default:
                        throw new \RuntimeException('Second parameter of child:: not recognised');
                }
                break;

            case 'following-sibling':
                switch ($this->getNode()) {
                    case '*':
                        if ($context instanceof DOMNodeList) {
                            if ($context->count() > 1 || $context->count() < 1) {
                                throw new \RuntimeException('following-sibling only needs 1 context node');
                            }

                            $context = $context->item(0);
                        }

                        $result = new DOMNodeList();

                        while ($context->nextSibling !== null) {
                            $result[] = $context->nextSibling;
                            $context = $context->nextSibling;

                        }

                        return $result;

                    default:
                        throw new \RuntimeException('Second parameter of following-sibling:: not recognised');
                }
                break;

            case 'ancestor-or-self':
                switch ($this->getNode()) {
                    case '*':
                        if ($context instanceof DOMNodeList) {
                            if ($context->count() !== 1) {
                                throw new \RuntimeException('ancestor-or-self');
                            }

                            $context = $context->item(0);
                        }

                        $items = new DOMNodeList($context);

                        while ($context->parentNode instanceof \DOMElement) {
                            $items[] = $context->parentNode;
                            $context = $context->parentNode;
                        }

                        return $items;

                    default:
                        throw new \RuntimeException('Second parameter of ancestor-or-self:: not recognised');
                }
                break;

            default:
                $msg = 'Pseudoelement ' . $this->getName() . '::' . $this->getNode() . 'not recognised';
                throw new \RuntimeException($msg);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
