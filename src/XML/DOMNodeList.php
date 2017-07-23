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
     * Items in the list.
     *
     * @var array
     */
    protected $items;

    /**
     * Constructor.
     *
     * @param mixed $items
     *
     * @throws RuntimeException
     */
    public function __construct($items = [])
    {
        if (is_array($items)) {
            $this->fromArray($items);
        } elseif ($items instanceof OriginalDOMNodeList) {
            $this->fromDOMNodeList($items);
        } elseif ($items instanceof \DOMNamedNodeMap) {
            $this->fromDOMNamedNodeMap($items);
        } elseif ($items instanceof DOMNode) {
            $this->fromDOMNode($items);
        } elseif ($items instanceof self) {
            $this->fromSelf($items);
        } elseif ($items === null) {
            $this->fromArray([]);
        } else {
            throw new RuntimeException('Given parameter not recognized: type ' . gettype($items));
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
     * Get an item by index.
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
    }

    public function offsetUnset($offset)
    {
        throw new RuntimeException('Not possible to unset a value in a ' . __CLASS__);
    }

    public function toArray()
    {
        return $this->items;
    }

    /**
     * Fills the object with the items in the given array.
     *
     * @param array $items
     */
    public function fromArray(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Fills the object with the items in the given OriginalDOMNodeList.
     *
     * @param OriginalDOMNodeList $items
     */
    public function fromDOMNodeList(OriginalDOMNodeList $items)
    {
        $this->items = [];

        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    /**
     * Fills the object with the items in the given OriginalDOMNodeList.
     *
     * @param \DOMNamedNodeMap $items
     */
    public function fromDOMNamedNodeMap(\DOMNamedNodeMap $items)
    {
        $this->items = [];

        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    /**
     * Fills the object with the given DOMNode item.
     *
     * @param DOMNode $item
     */
    public function fromDOMNode(DOMNode $item)
    {
        $this->items = [$item];
    }

    /**
     * Fills the object with the given self DOMNodeList item.
     *
     * @param DOMNodeList $item
     */
    public function fromSelf(DOMNodeList $item)
    {
        $this->items = $item->toArray();
    }

    public function merge(DOMNodeList ...$list)
    {
        $list = array_map(function (DOMNodeList $value) {
            return $value->toArray();
        }, $list);

        array_unshift($list, $this->items);

        $this->items = array_merge(...$list);
    }

    /**
     * Returns the number of nodes the object contains.
     *
     * @return int
     */
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
     * Sorts the elements contained in apparition order.
     */
    public function sort()
    {
        if (count($this->items) <= 1) {
            return;
        }

        usort($this->items, function (\DOMNode $a, \DOMNode $b) {
            if ($a->isSameNode($b)) {
                return 0;
            }

            if ($a instanceof DOMDocument || $a instanceof \DOMComment) {
                return -1;
            }

            if ($b instanceof DOMDocument || $b instanceof \DOMComment) {
                return 1;
            }

            $levelsA = [$a];
            $levelsB = [$b];

            // Determine all the levels
            $topParentA = $a;

            while ($topParentA !== null && !$topParentA->parentNode instanceof \DOMDocument) {
                $topParentA = $topParentA->parentNode;
                $levelsA[] = $topParentA;
            }

            $topParentB = $b;

            while ($topParentB !== null && !$topParentB->parentNode instanceof \DOMDocument) {
                $topParentB = $topParentB->parentNode;
                $levelsB[] = $topParentB;
            }

            $levelsA = array_reverse($levelsA);
            $levelsB = array_reverse($levelsB);

            // Check where is the difference
            $min = min(count($levelsA), count($levelsB));

            for ($i = 0; $i < $min; ++$i) {
                if ($levelsA[$i] === $levelsB[$i]) {
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

            if (count($levelsA) < count($levelsB)) {
                return -1;
            }

            if (count($levelsA) > count($levelsB)) {
                return 1;
            }

            return 0;
        });
    }

    /**
     * Deletes duplicates from the elements contained in the list.
     */
    public function unique()
    {
        $newItems = [];

        foreach ($this->items as $item) {
            if (!in_array($item, $newItems, true)) {
                $newItems[] = $item;
            }
        }

        $this->items = $newItems;
    }
}
