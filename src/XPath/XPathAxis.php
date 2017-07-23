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
     * @var ExpressionInterface
     */
    protected $node;

    /**
     * @inheritdoc
     */
    public static function parseXPath($string)
    {
        if (strpos($string, '::') === false) {
            return false;
        }

        $parts = explode('::', $string);
        $obj = new self();

        $factory = new Factory();

        $obj->setName($parts[0]);
        $obj->setNode($factory->create($parts[1]));

        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return $this->getName() . '::' . (string) $this->getNode();
    }

    /**
     * @inheritdoc
     */
    protected function evaluateExpression($context)
    {
        return $this->query($context);
    }

    /**
     * @return ExpressionInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param ExpressionInterface $node
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

        $selectedNodes = [];
        $node = $this->getNode();
        $nodeString = $node->toString();

        // Select the nodes in each axis

        switch ($this->getName()) {
            case 'child':
                $selectedNodes[] = [];

                foreach ($context as $contextNode) {
                    $selectedNodes[] = (new DOMNodeList($contextNode->childNodes))->toArray();
                }

                $selectedNodes = array_merge(...$selectedNodes);
                break;

            case 'self':
                $selectedNodes = $context->toArray();
                break;

            case 'namespace':
                // That is an special case: we need to use the normal xPath
                switch ($this->getNode()->toString()) {
                    case '*':
                        // FIXME: If no context, this fails
                        foreach ($context as $contextNode) {
                            $xPathClass = new \DOMXPath($contextNode instanceof \DOMDocument ? $contextNode : $contextNode->ownerDocument);
                            $results1 = $xPathClass->query('namespace::*', $contextNode);
                        }

                        return new DOMNodeList($results1);

                    default:
                        $msg = 'Second parameter of namespace:: not recognised: ' . $this->getNode()->toString();
                        throw new \RuntimeException($msg);
                }
                break;

            case 'attribute':
                switch ($this->getNode()->toString()) {
                    // That is an special case: even if using '*', attr are not DOMElements
                    case '*':
                        // Result DOMXPath
                        $result = new DOMNodeList();

                        foreach ($context as $contextNode) {
                            $result->merge(new DOMNodeList($contextNode->attributes));
                        }

                        return $result;

                    default:
                        $msg = 'Second parameter of attribute:: not recognised: ' . $this->getNode()->toString();
                        throw new \RuntimeException($msg);
                }
                break;

            case 'following':
                $selectedNodes[] = [];

                foreach ($context as $contextNode) {
                    while ($contextNode) {
                        // First, extract all next siblings with descendants
                        while ($contextNode->nextSibling !== null) {
                            $selectedNodes[] = $this->getAllNodesDeep($contextNode->nextSibling);
                            $contextNode = $contextNode->nextSibling;
                        }

                        // Now, search for the next sibling of the parent
                        $contextNode = $contextNode->parentNode;
                    }
                }

                $selectedNodes = array_merge(...$selectedNodes);
                break;

            case 'preceding':
                $selectedNodes[] = [];

                foreach ($context as $contextNode) {

                    while ($contextNode) {
                        while ($contextNode->previousSibling !== null) {
                            $selectedNodes[] = $this->getAllNodesDeep($contextNode->previousSibling);
                            $contextNode = $contextNode->previousSibling;
                        }

                        $contextNode = $contextNode->previousSibling;
                    }
                }

                $selectedNodes = array_merge(...$selectedNodes);
                break;

            case 'following-sibling':
                foreach ($context as $contextNode) {
                    while ($contextNode->nextSibling !== null) {
                        $selectedNodes[] = $contextNode->nextSibling;
                        $contextNode = $contextNode->nextSibling;
                    }
                }
                break;

            case 'preceding-sibling':
                foreach ($context as $contextNode) {
                    while ($contextNode->previousSibling !== null) {
                        $selectedNodes[] = $contextNode->previousSibling;
                        $contextNode = $contextNode->previousSibling;
                    }
                }
                break;

            case 'ancestor-or-self':
                foreach ($context as $contextNode) {
                    if (!$contextNode instanceof \DOMDocument) {
                        $selectedNodes[] = $contextNode;
                    }

                    while ($contextNode->parentNode instanceof \DOMElement) {
                        $selectedNodes[] = $contextNode = $contextNode->parentNode;
                    }
                }

                break;

            case 'ancestor':
                foreach ($context as $contextNode) {
                    while ($contextNode->parentNode instanceof \DOMElement) {
                        $selectedNodes[] = $contextNode = $contextNode->parentNode;
                    }
                }

                break;

            case 'parent':
                foreach ($context as $contextNode) {
                    $selectedNodes[] = $contextNode->parentNode;
                }

                break;

            case 'descendant':
                $selectedNodes[] = [];

                foreach ($context as $contextNode) {
                    $items = $this->getAllNodesDeep($contextNode);
                    array_shift($items);

                    $selectedNodes[] = $items;
                }

                $selectedNodes = array_merge(...$selectedNodes);
                break;

            default:
                $msg = 'Pseudoelement ' . $this->getName() . '::' . $this->getNode()->toString() . ' not recognised';
                throw new \RuntimeException($msg);
        }

        // Filter
        try {
            if ($node instanceof XPathPathNode) {
                if ($nodeString === '*') {
                    $selectedNodes = array_filter($selectedNodes, function ($value) {
                        return $value instanceof \DOMElement;
                    });
                } elseif (preg_match('#^[a-z0-9-]+$#i', $nodeString)) {
                    $selectedNodes = array_filter($selectedNodes, function ($value) use ($nodeString) {
                        return $value instanceof \DOMElement && $value->nodeName === $nodeString;
                    });
                } else {
                    throw new \RuntimeException('');
                }
            } elseif ($node instanceof XPathFunction) {
                if ($nodeString === 'fn:comment()') {
                    $selectedNodes = array_filter($selectedNodes, function ($value) {
                        return $value instanceof \DOMComment;
                    });
                } elseif ($nodeString === 'fn:processing-instruction()') {
                    $selectedNodes = array_filter($selectedNodes, function ($value) {
                        return $value instanceof \DOMProcessingInstruction;
                    });
                } elseif ($nodeString === 'fn:text()') {
                    $selectedNodes = array_filter($selectedNodes, function ($value) {
                        return $value instanceof \DOMCharacterData && !$value instanceof \DOMComment;
                    });
                } else {
                    throw new \RuntimeException('');
                }
            } else {
                throw new \RuntimeException('');
            }
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Axis right part "' . $nodeString . '" not implemented');
        }

        $selectedNodes = new DOMNodeList($selectedNodes);
        return $selectedNodes;
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
