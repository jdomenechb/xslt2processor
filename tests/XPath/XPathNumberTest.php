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

use Jdomenechb\XSLT2Processor\XPath\Exception\NotXPathNumber;
use Jdomenechb\XSLT2Processor\XPath\XPathNumber;
use PHPUnit\Framework\TestCase;

/**
 * Test for the XPathNumber class.
 *
 * @author jdomenechb
 */
class XPathNumberTest extends TestCase
{
    /**
     * Test if the xPath given is a valid number
     * @dataProvider invalidValuesProvider
     * @param mixed $xPath
     */
    public function testInvalid($xPath)
    {
        $this->expectException(NotXPathNumber::class);
        $obj = new XPathNumber($xPath);
    }

    /**
     * Test if the representation of the given xPath remains the same
     * @dataProvider basicValuesProvider
     * @param mixed $xPath
     */
    public function testToString($xPath)
    {
        $obj = new XPathNumber($xPath);
        $this->assertSame((string) $xPath, $obj->toString());
    }

    /**
     * Test if the representation of the given xPath remains the same
     * @param mixed $xPath
     */
    public function testUselessDefaultNamespacePrefix()
    {
        $obj = new XPathNumber(2);
        $obj2 = new XPathNumber(2);
        $obj2->setDefaultNamespacePrefix('something');
        $this->assertEquals($obj, $obj2);
    }


    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function basicValuesProvider()
    {
        return [
            [0],
            [1],
            ['0'],
            ['1'],
            [-7],
            [14],
            [-14],
            [78.1847],
            [-45.7352],
            [9865674.978677566],
            [-9865674.978677566],
        ];
    }

    public function invalidValuesProvider()
    {
        return [
            ['a'],
            ['1-'],
            ['46.'],
            ['.98'],
            ['-'],
            ['.'],
            ['jvgku456hbkb'],
        ];
    }
}
