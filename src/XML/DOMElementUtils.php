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

use DOMCharacterData;
use DOMElement;
use DOMText;

/**
 * Help methods to ease working with DOMElement objects.
 *
 * @author jdomenechb
 */
class DOMElementUtils
{
    /**
     * Returns the last writable node to be found in a element, creating one if none found.
     *
     * @param DOMElement $node
     * @param array      $cdataSections
     *
     * @return DOMCharacterData
     */
    public function getWritableNodeIn(DOMElement $node, array $cdataSections = [])
    {
        // If it has no child nodes, we must create a base text or CDATA node.
        if (!$node->childNodes->length) {
            $this->addWritableNodeTo($node, $cdataSections);
        }

        // We try to get the last writable child node: the last or a new one
        $latestNode = $node->childNodes->item($node->childNodes->length - 1);

        if ($latestNode instanceof DOMText) {
            $writableNode = $node->childNodes->item($node->childNodes->length - 1);
        } else {
            $writableNode = $this->addWritableNodeTo($node, $cdataSections);
        }

        return $writableNode;
    }

    /**
     * Appends a writable node to the node specified.
     *
     * @param DOMElement $node
     * @param array      $cDataSections
     *
     * @return DOMText
     */
    public function addWritableNodeTo(DOMElement $node, array $cDataSections = [])
    {
        if (in_array($node->nodeName, $cDataSections)) {
            $textNode = $node->ownerDocument->createCDATASection('');
        } else {
            $textNode = $node->ownerDocument->createTextNode('');
        }

        return $node->appendChild($textNode);
    }

    public function removeAllChildren(DOMElement $node)
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            $node->removeChild($child);
        }
    }

    public function removeAllAttributes(DOMElement $node)
    {
        $attributes = [];

        foreach ($node->attributes as $attribute) {
            $attributes[] = $attribute->nodeName;
        }

        foreach ($attributes as $attribute) {
            $node->removeAttribute($attribute);
        }
    }
}
