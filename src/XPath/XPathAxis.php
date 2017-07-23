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

class XPathAxis extends AbstractXPath
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $node;

    public static function parseXPath($string)
    {
        if (strpos($string, '::') === false) {
            return false;
        }

        $parts = explode('::', $string);
        $obj = new self();

        $obj->setName($parts[0]);
        $obj->setNode($parts[1]);

        return $obj;
    }

    public function toString()
    {
        return $this->getName() . '::' . $this->getNode();
    }

    protected function evaluateExpression ($context)
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

    /**
     * @inheritdoc
     */
    public function query($context)
    {
        if (!$context instanceof DOMNodeList) {
            $context = new DOMNodeList($context);
        }

        $nodeName = $this->getNode();

        switch ($this->getName()) {
            case 'child':
                switch ($nodeName) {
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

            case 'self':
                switch ($nodeName) {
                    case 'comment()':
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            if (!$contextNode instanceof \DOMComment) {
                                continue;
                            }

                            $result[] = $contextNode;
                        }

                        return $result;

                    case '*':
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            if (!$contextNode instanceof \DOMElement) {
                                continue;
                            }

                            $result[] = $contextNode;
                        }

                        return $result;

                    case 'processing-instruction()':
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            if (!$contextNode instanceof \DOMProcessingInstruction) {
                                continue;
                            }

                            $result[] = $contextNode;
                        }

                        return $result;

                    case 'text()':
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            if (!$contextNode instanceof \DOMCharacterData) {
                                continue;
                            }

                            $result[] = $contextNode;
                        }

                        return $result;

                    default:
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            if (!$contextNode instanceof \DOMElement || $contextNode->localName !== $nodeName) {
                                continue;
                            }

                            $result[] = $contextNode;
                        }

                        return $result;
                }
                break;

            case 'namespace':
                switch ($nodeName) {
                    case '*':
                        // Result DOMXPath
                        foreach ($context as $contextNode) {
                            $xPathClass = new \DOMXPath($contextNode instanceof \DOMDocument ? $contextNode : $contextNode->ownerDocument);
                            $results1 = $xPathClass->query('namespace::*', $contextNode);
                        }

                        return new DOMNodeList($results1);

                    default:
                        throw new \RuntimeException('Second parameter of namespace:: not recognised: ' . $nodeName);
                }
                break;

            case 'attribute':
                switch ($nodeName) {
                    case '*':
                        // Result DOMXPath
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            $result->merge(new DOMNodeList($contextNode->attributes));
                        }

                        return $result;

                    default:
                        throw new \RuntimeException('Second parameter of attribute:: not recognised: ' . $nodeName);
                }
                break;

            case 'following':
                if ($context instanceof DOMNodeList) {
                    $count = $context->count();

                    if ($count > 1 || $count < 1) {
                        throw new \RuntimeException('following only needs 1 context node');
                    }

                    $context = $context->item(0);
                }

                $result = [[]];

                while ($context) {
                    // First, extract all next siblings with descendants
                    while ($context->nextSibling !== null) {
                        $result[] = $this->getAllNodesDeep($context->nextSibling);
                        $context = $context->nextSibling;
                    }

                    // Now, search for the next sibling of the parent
                    $context = $context->parentNode;
                }

                $result = array_merge(...$result);

                if ($nodeName === '*') {
                    $result = array_filter($result, function ($value) {
                        return $value instanceof \DOMElement;
                    });
                } elseif (preg_match('#^[a-z0-9-]+$#i', $nodeName)) {
                    $result = array_filter($result, function ($value) use ($nodeName) {
                        return $value->nodeName === $nodeName && $value instanceof \DOMElement;
                    });
                } else {
                    throw new \RuntimeException('Second parameter of following:: not recognised: ' . $nodeName);
                }

                return new DOMNodeList($result);

                break;

            case 'preceding':
                if ($context instanceof DOMNodeList) {
                    $count = $context->count();

                    if ($count > 1 || $count < 1) {
                        throw new \RuntimeException('preceding only needs 1 context node');
                    }

                    $context = $context->item(0);
                }

                $result = [[]];

                while ($context) {
                    while ($context->previousSibling !== null) {
                        $result[] = $this->getAllNodesDeep($context->previousSibling);
                        $context = $context->previousSibling;
                    }

                    $context = $context->previousSibling;
                }

                $result = array_merge(...$result);

                if ($nodeName === '*') {
                    $result = array_filter($result, function ($value) {
                        return $value instanceof \DOMElement;
                    });
                } elseif (preg_match('#^[a-z0-9-]+$#i', $nodeName)) {
                    $result = array_filter($result, function ($value) use ($nodeName) {
                        return $value->nodeName === $nodeName && $value instanceof \DOMElement;
                    });
                } else {
                    throw new \RuntimeException('Second parameter of following:: not recognised: ' . $nodeName);
                }

                return new DOMNodeList($result);

                break;

            case 'following-sibling':
                if (strpos($nodeName, '(') !== false) {
                    throw new \RuntimeException(
                        'Second parameter of following-sibling:: not recognised: ' . $nodeName
                    );
                }

                $result = [];

                foreach ($context as $contextNode) {
                    while ($contextNode->nextSibling !== null) {
                        if (
                            $contextNode->nextSibling instanceof \DOMElement
                            && ($nodeName === '*' || $nodeName === $contextNode->nextSibling->nodeName)
                        ) {
                            $result[] = $contextNode->nextSibling;
                        }

                        $contextNode = $contextNode->nextSibling;
                    }
                }

                return new DOMNodeList($result);

            case 'preceding-sibling':
                if (strpos($nodeName, '(') !== false) {
                    throw new \RuntimeException(
                        'Second parameter of preceding-sibling:: not recognised: ' . $nodeName
                    );
                }

                if ($context instanceof DOMNodeList) {
                    $count = $context->count();

                    if ($count > 1 || $count < 1) {
                        throw new \RuntimeException('preceding-sibling only needs 1 context node');
                    }

                    $context = $context->item(0);
                }

                $result = new DOMNodeList();

                while ($context->previousSibling !== null) {
                    if (
                        $context->previousSibling instanceof \DOMElement
                        && ($nodeName === '*' || $nodeName === $context->previousSibling->nodeName)
                    ) {
                        $result[] = $context->previousSibling;
                    }

                    $context = $context->previousSibling;
                }

                $result->sort();

                return $result;

                break;

            case 'ancestor-or-self':
                switch ($nodeName) {
                    case '*':
                        if ($context instanceof DOMNodeList) {
                            if ($context->count() !== 1) {
                                throw new \RuntimeException('ancestor-or-self');
                            }

                            $context = $context->item(0);
                        }

                        if (!$context instanceof \DOMDocument) {
                            $items = new DOMNodeList($context);
                        } else {
                            $items = new DOMNodeList();
                        }

                        while ($context->parentNode instanceof \DOMElement) {
                            $items[] = $context->parentNode;
                            $context = $context->parentNode;
                        }

                        //$items->sort();

                        return $items;

                    default:
                        throw new \RuntimeException('Second parameter of ancestor:: not recognised');
                }
                break;

            case 'ancestor':
                switch ($nodeName) {
                    case '*':
                        if ($context instanceof DOMNodeList) {
                            if ($context->count() !== 1) {
                                throw new \RuntimeException('ancestor');
                            }

                            $context = $context->item(0);
                        }

                        $items = new DOMNodeList();

                        while ($context->parentNode instanceof \DOMElement) {
                            $items[] = $context = $context->parentNode;
                        }

                        return $items;

                    default:
                        throw new \RuntimeException('Second parameter of ancestor:: not recognised');
                }

                break;

            case 'parent':
                switch ($nodeName) {
                    case '*':
                        if ($context instanceof DOMNodeList) {
                            if ($context->count() !== 1) {
                                throw new \RuntimeException('parent');
                            }

                            $context = $context->item(0);
                        }

                        return new DOMNodeList($context->parentNode);


                    default:
                        throw new \RuntimeException('Second parameter of parent:: not recognised');
                }
                break;

            case 'descendant':
                if ($context instanceof DOMNodeList) {
                    if ($context->count() !== 1) {
                        throw new \RuntimeException('descendant');
                    }

                    $context = $context->item(0);
                }

                $items = $this->getAllNodesDeep($context);
                array_shift($items);

                switch ($nodeName) {
                    case '*':
                        $items = array_filter($items, function ($value) {
                            return $value instanceof \DOMElement;
                        });

                        return new DOMNodeList($items);

                    default:
                        throw new \RuntimeException('Second parameter of descendant:: not recognised');
                }

                break;

            default:
                $msg = 'Pseudoelement ' . $this->getName() . '::' . $nodeName . ' not recognised';
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

    /**
     * Returns the given node and all their childs in a flat array.
     *
     * @param \DOMNode $node
     * @return array
     */
    protected function getAllNodesDeep(\DOMNode $node)
    {
        $nodes = [[$node]];

        if ($node->childNodes) {
            foreach ($node->childNodes as $childNode) {
                $nodes[] = $this->getAllNodesDeep($childNode);
            }
        }

        $nodes = array_merge(...$nodes);

        return $nodes;
    }

}
