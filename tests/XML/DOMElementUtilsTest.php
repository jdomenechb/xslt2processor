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

use DOMCdataSection;
use DOMCharacterData;
use DOMDocument;
use DOMText;
use Jdomenechb\XSLT2Processor\XML\DOMElementUtils;
use PHPUnit\Framework\TestCase;

/**
 * Test for the Jdomenechb\XSLT2Processor\XML\DOMElementUtils class.
 *
 * @author jdomenechb
 */
class DOMElementUtilsTest extends TestCase
{
    /**
     * Tests that the method adds the correct node in each case
     * @dataProvider cdataCasesProvider
     * @param array $cdataCases
     * @param string $classType
     */
    public function testAddWritableNodeTo(array $cdataCases, $classType)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $result = $domElementUtils->addWritableNodeTo($domElement, $cdataCases);
        $this->assertInstanceOf($classType, $result);
        $this->assertSame($domElement, $result->parentNode);
    }

    /**
     * Tests that the method adds the correct node in each case when the node contains elements
     * @dataProvider cdataCasesProvider
     * @param array $cdataCases
     * @param string $classType
     */
    public function testAddWritableNodeToAlreadyExisting(array $cdataCases, $classType)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');
        $firstTextNode = new DOMText('other');
        $domElement->appendChild($firstTextNode);

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $result = $domElementUtils->addWritableNodeTo($domElement, $cdataCases);
        $this->assertInstanceOf($classType, $result);
        $this->assertSame($domElement, $result->parentNode);
        $this->assertNotSame($firstTextNode, $result);
    }

    /**
     * Tests that the method returns the correct object for DOMElements that contain no nodes
     * @dataProvider cdataCasesProvider
     * @param array $cdataCases
     * @param string $classType
     */
    public function testGetNoWritableNode(array $cdataCases, $classType)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $result = $domElementUtils->getWritableNodeIn($domElement, $cdataCases);
        $this->assertInstanceOf($classType, $result);
        $this->assertSame($domElement, $result->parentNode);
    }

    /**
     * Tests that the method returns the correct object for DOMElements that contain only one node
     * @dataProvider writableNodeProvider
     * @param DOMCharacterData $node
     */
    public function testGetOnlyWritableNode(DOMCharacterData $node)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');
        $domElement->appendChild($node);

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $this->assertSame($node, $domElementUtils->getWritableNodeIn($domElement));
    }

    /**
     * Tests that the method returns the correct object for DOMElements that contain multiple text nodes
     * @dataProvider writableNodeProvider
     * @param DOMCharacterData $node
     */
    public function testGetMultipleWritableNode(DOMCharacterData $node)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');
        $domElement->appendChild(new DOMText('other'));
        $domElement->appendChild($node);

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $this->assertSame($node, $domElementUtils->getWritableNodeIn($domElement));
    }

    /**
     * Tests that the method returns the correct object for DOMElements that contain multiple nodes, not only text
     * @dataProvider writableNodeProvider
     * @param DOMCharacterData $node
     */
    public function testGetMultipleWithOtherWritableNode(DOMCharacterData $node)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');
        $domElement->appendChild(new DOMText('other'));
        $domElement->appendChild($xml->createElement('other2'));
        $domElement->appendChild($node);

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $this->assertSame($node, $domElementUtils->getWritableNodeIn($domElement));
    }

    /**
     * Tests that the method returns the correct object for DOMElements that multiple nodes with DOMElements but not a
     * last writable node
     * @dataProvider cdataCasesProvider
     * @param array $cdataCases
     * @param string $classType
     */
    public function testGetMultipleWithOtherButNoWritableNode(array $cdataCases, $classType)
    {
        // Prepare the test case
        $xml = new DOMDocument();
        $domElement = $xml->createElement('test');

        $firstTextNode = new DOMText('other');
        $domElement->appendChild($firstTextNode);
        $domElement->appendChild($xml->createElement('other2'));

        // Extract the node
        $domElementUtils = new DOMElementUtils();
        $result = $domElementUtils->getWritableNodeIn($domElement, $cdataCases);
        $this->assertInstanceOf($classType, $result);
        $this->assertSame($domElement, $result->parentNode);
        $this->assertNotSame($firstTextNode, $result);
    }

    /**
     * Provider of writable nodes.
     * @return array
     */
    public function writableNodeProvider()
    {
        return [
            [new DOMText(rand(0, 9999))],
            [new DOMCdataSection(rand(0, 9999))],
        ];
    }

    /**
     * Provider of cdata cases.
     * @return array
     */
    public function cdataCasesProvider()
    {
        return [
            [[], DOMText::class],
            [['test'], DOMCdataSection::class],
        ];
    }
}
