<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\Tests\XML;

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

/**
 * Class DOMNodeListTest for testing DOMNodeList.
 *
 * @author jdomemechb
 */
class DOMNodeListTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that importing a DOMNodeList is possible.
     */
    public function testFromDOMNodeList()
    {
        // Setup a basic XML
        $xml = new \DOMDocument();

        $root = $xml->createElement('root');
        $xml->appendChild($root);

        $elementA = $xml->createElement('a');
        $root->appendChild($elementA);

        $elementB = $xml->createElement('b');
        $root->appendChild($elementB);

        $elementC = $xml->createElement('c');
        $root->appendChild($elementC);

        // Perform an XPath on the XML to obtain the DOMNodeList
        $xPath = new \DOMXPath($xml);
        $originalDOMNodeList = $xPath->query('/*/*');

        // Create the object and check
        $newDOMNodeList = new DOMNodeList();
        $newDOMNodeList->fromDOMNodeList($originalDOMNodeList);

        $this->assertSame([$elementA, $elementB, $elementC], $newDOMNodeList->toArray());

        return $newDOMNodeList;
    }

    /**
     * Tests that importing a DOMNode is possible.
     */
    public function testFromDOMNode()
    {
        // Setup a basic XML
        $xml = new \DOMDocument();

        $root = $xml->createElement('root');

        // Create the object and check
        $newDOMNodeList = new DOMNodeList();
        $newDOMNodeList->fromDOMNode($root);

        $this->assertSame([$root], $newDOMNodeList->toArray());
    }

    /**
     * Tests that importing from another DOMNodeList is possible.
     *
     * @depends testFromDOMNodeList
     *
     * @param DOMNodeList $oldDOMNodeList
     */
    public function testFromSelf(DOMNodeList $oldDOMNodeList)
    {
        $newDOMNodeList = new DOMNodeList();
        $newDOMNodeList->fromSelf($oldDOMNodeList);

        $this->assertSame($oldDOMNodeList->toArray(), $newDOMNodeList->toArray());
    }

    /**
     * Tests that importing from an array is possible.
     *
     * @depends testFromDOMNodeList
     *
     * @param DOMNodeList $oldDOMNodeList
     */
    public function testFromArray(DOMNodeList $oldDOMNodeList)
    {
        $newDOMNodeList = new DOMNodeList();
        $newDOMNodeList->fromArray($oldDOMNodeList->toArray());

        $this->assertSame($oldDOMNodeList->toArray(), $newDOMNodeList->toArray());
    }

    /**
     * Tests that an empty DOMNodeList is effectively empty.
     */
    public function testEmpty()
    {
        $newDOMNodeList = new DOMNodeList();
        $this->assertSame([], $newDOMNodeList->toArray());
    }

    /**
     * Tests that the DOMNodeList can be counted.
     *
     * @dataProvider multipleLengthProvider
     *
     * @param array $items
     */
    public function testCount(array $items)
    {
        $newDOMNodeList = new DOMNodeList($items);
        $this->assertCount($newDOMNodeList->count(), $items);
    }

    /**
     * Tests that the DOMNodeList can be counted via length attribute.
     *
     * @dataProvider multipleLengthProvider
     *
     * @param array $items
     */
    public function testLength(array $items)
    {
        $newDOMNodeList = new DOMNodeList($items);
        $this->assertCount($newDOMNodeList->length, $items);
    }

    /**
     * Tests that the DOMNodeList returns an error if trying to access a non valid property.
     */
    public function testNotValidProperty()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Property invalidProperty not available');

        $newDOMNodeList = new DOMNodeList();
        $newDOMNodeList->invalidProperty;
    }

    /**
     * Tests getting a single item.
     *
     * @depends testFromDOMNodeList
     *
     * @param DOMNodeList $oldDOMNodeList
     */
    public function testItem(DOMNodeList $oldDOMNodeList)
    {
        $items = $oldDOMNodeList->toArray();

        foreach ($items as $key => $item) {
            $this->assertSame($item, $oldDOMNodeList->item($key));
        }
    }

    /**
     * Provider of DOMNodeList with multiple lengths.
     *
     * @return array
     */
    public function multipleLengthProvider()
    {
        $xml = new \DOMDocument();

        $root = $xml->createElement('root');
        $xml->appendChild($root);

        $elementA = $xml->createElement('a');
        $root->appendChild($elementA);

        $elementB = $xml->createElement('b');
        $root->appendChild($elementB);

        $elementC = $xml->createElement('c');
        $root->appendChild($elementC);

        return [
            [[$root]],
            [[$root, $elementA]],
            [[$root, $elementA, $elementB]],
            [[$root, $elementA, $elementB, $elementC]],
        ];
    }
}
