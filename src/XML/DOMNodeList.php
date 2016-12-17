<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XML;

use ArrayAccess;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList as OriginalDOMNodeList;
use Iterator;
use RuntimeException;

/**
 * Class for offering a set of nodes, replacing \DOMNodeList, capable of importing node lists form multiple formats.
 *
 * @author jdomenechb
 */
class DOMNodeList implements ArrayAccess, Iterator
{
    /**
     * Items holded in the list.
     *
     * @var array
     */
    protected $items;

    /**
     * Defines if the list must be considered as a parent for processing (for example, when it's a set of variables).
     *
     * @var bools
     */
    protected $parent = false;

    /**
     * Constructor.
     *
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        if ($items instanceof OriginalDOMNodeList) {
            $this->fromDOMNodeList($items);
        } elseif ($items instanceof DOMNode) {
            $this->fromArray([$items]);
        } elseif ($items instanceof self) {
            $this->fromArray($items->toArray());
        } elseif (is_array($items)) {
            $this->fromArray($items);
        } else {
            $this->items = [];
        }
    }

    /**
     * Add support for var length, like \DOMNodeList.
     *
     * @param string $name
     *
     * @throws RuntimeException
     *
     * @return int
     */
    public function __get($name)
    {
        if ($name !== 'length') {
            throw new RuntimeException('Property ' . $name . ' not available');
        }

        return $this->count();
    }

    /**
     * Get an item vy index.
     *
     * @param int $index
     *
     * @return DOMNode
     */
    public function item($index)
    {
        return $this->items[$index];
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset) {
            $this->items[$offset] = $value;
        } else {
            $this->items[] = $value;
        }

        $this->sort();
    }

    public function offsetUnset($offset)
    {
        throw new RuntimeException('Not possible to unset a value in a ' . __CLASS__);
    }

    public function toArray()
    {
        return $this->items;
    }

    public function fromArray(array $items)
    {
        $this->items = $items;
        $this->sort();
    }

    public function fromDOMNodeList(OriginalDOMNodeList $items)
    {
        $newArray = [];

        foreach ($items as $item) {
            $newArray[] = $item;
        }

        $this->items = $newArray;
    }

    public function merge(DOMNodeList ...$list)
    {
        $result = $this->items;

        foreach ($list as $arg) {
            $result = array_merge($result, $arg->toArray());
        }

        $this->items = $result;

        $this->sort();
    }

    public function count()
    {
        return count($this->items);
    }

    public function current()
    {
        return current($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function next()
    {
        return next($this->items);
    }

    public function rewind()
    {
        reset($this->items);
    }

    public function valid()
    {
        $key = key($this->items);

        return $key !== null && $key !== false;
    }

    /**
     * Returns if the list must be considered as a parent for processing (for example, when it's a set of variables).
     *
     * @var bools
     */
    public function isParent()
    {
        return $this->parent;
    }

    /**
     * Sets if the list must be considered as a parent for processing (for example, when it's a set of variables).
     *
     * @var bools
     *
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    protected function sort()
    {
        if (count($this->items) <= 1) {
            return;
        }

        usort($this->items, function (\DOMNode $a, \DOMNode $b) {
            if ($a->isSameNode($b)) {
                return 0;
            }

            $levelsA = [$a];
            $levelsB = [$b];

            // Determine all the levels
            $topParentA = $a;

            while (!$topParentA->parentNode instanceof \DOMDocument) {
                $topParentA = $topParentA->parentNode;
                array_unshift($levelsA, $topParentA);
            }

            $topParentB = $b;

            while (!$topParentB->parentNode instanceof \DOMDocument) {
                $topParentB = $topParentB->parentNode;
                array_unshift($levelsB, $topParentB);
            }

            // Check where is the difference
            $min = min(count($levelsA), count($levelsB));

            for ($i = 0; $i < $min; ++$i) {
                if ($levelsA[$i] == $levelsB[$i]) {
                    continue;
                }

                // At this point, the nodes differ. Let's see in what order they are.
                foreach ($levelsA[$i - 1]->childNodes as $sibling) {
                    if ($sibling->isSameNode($levelsA[$i])) {
                        return -1;
                    }

                    if ($sibling->isSameNode($levelsB[$i])) {
                        return 1;
                    }
                }
            }
        });
    }

    protected function sortByExam()
    {
        if (count($this->items) <= 1) {
            return;
        }

        reset($this->items);
        $first = current($this->items);

        if ($first instanceof DOMDocument) {
            $doc = $first;
        } else {
            $doc = $first->ownerDocument;
        }

        $newResults = [];
        $this->recursiveRelativeSort($newResults, $doc->documentElement);

        if ($newResults) {
            $this->items = $newResults;
        } else {
            $this->items = array_values($this->items);
        }
    }

    protected function recursiveRelativeSort(&$newResults, DOMNode $node)
    {
        if (!$this->items) {
            return;
        }

        // Check if the current node is in the list
        foreach ($this->items as $key => $item) {
            if ($node->isSameNode($item)) {
                $newResults[] = $item;
                unset($this->items[$key]);
                break;
            }
        }

        // Treat all childs

        if (!$node instanceof DOMElement) {
            return;
        }

        foreach ($node->childNodes as $childNode) {
            $this->recursiveRelativeSort($newResults, $childNode);
        }
    }
}
