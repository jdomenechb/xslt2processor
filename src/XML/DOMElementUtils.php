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

use DOMCdataSection;
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
            $this->addWritableNodeTo($node);
        }

        // We try to get the last child node
        if (
            $node->childNodes->item($node->childNodes->length - 1) instanceof DOMCdataSection
            || $node->childNodes->item($node->childNodes->length - 1) instanceof DOMText
        ) {
            // It is a writable node: we have what we wanted
            $writableNode = $node->childNodes->item($node->childNodes->length - 1);
        } else {
            // It contains another non-text element: we add one at the end
            $writableNode = $this->addWritableNodeTo($node);
        }

        return $writableNode;
    }

    /**
     * Appends a writable node to the node specified.
     *
     * @param DOMElement $node
     * @param array      $cDataSections
     *
     * @return DOMCharacterData
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
}
