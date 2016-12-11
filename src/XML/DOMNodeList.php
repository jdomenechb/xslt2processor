<?php

/*
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XML;

use DOMNodeList as OriginalDOMNodeList;

/**
 * Description of DOMNodeList
 *
 * @author jdomenechb
 */
class DOMNodeList implements \ArrayAccess, \Iterator
{
    protected $items;
    private $length;
    protected $parent = false;

    public function __construct($items = [])
    {
        if ($items instanceof \DOMNodeList) {
            $this->fromDOMNodeList($items);
        } elseif ($items instanceof \DOMNode) {
            $this->fromArray([$items]);
        } elseif ($items instanceof DOMNodeList) {
            $this->fromArray($items->toArray());
        } elseif (is_array($items)) {
            $this->fromArray($items);
        } else {
            $this->items = [];
        }
    }

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
        throw new \RuntimeException('Not possible to unset a value in a ' . __CLASS__);
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

    protected function sort()
    {
        if (count($this->items) <= 1) {
            return;
        }

        reset($this->items);
        $first = current($this->items);

        if ($first instanceof \DOMDocument) {
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

    public function recursiveRelativeSort(&$newResults, \DOMNode $node)
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

        if (!$node instanceof \DOMElement) {
            return;
        }

        foreach ($node->childNodes as $childNode) {
            $this->recursiveRelativeSort($newResults, $childNode);
        }
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
        return ($key !== NULL && $key !== FALSE);
    }

    public function isParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

}
