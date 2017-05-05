<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\Tests\XPath;

use DOMDocument;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Test for the Factory class.
 *
 * @author jdomenechb
 */
class FactoryTest extends TestCase
{
    /**
     * Test that the priority between every-level element and a selector is correct.
     */
    public function testPriorityWithSelector()
    {
        // Create the base XML
        $xml = new DOMDocument();
        $root = $xml->appendChild($xml->createElement('root'));

        $subOne = $root->appendChild($xml->createElement('subone'));
        $cFirst = $subOne->appendChild($xml->createElement('c'));

        $subTwo = $root->appendChild($xml->createElement('subtwo'));
        $cSecond = $subTwo->appendChild($xml->createElement('c'));

        // Calculate what is expected
        $expected = new DOMNodeList([$cFirst, $cSecond]);

        // Execute the operation
        $factory = new Factory();
        $xPath = $factory->create('//c[1]');

        $this->assertEquals($expected, $xPath->evaluate($xml));
    }
}
