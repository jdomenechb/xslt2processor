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
use Jdomenechb\XSLT2Processor\XPath\Exception\NotValidXPathElement;
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
     * Test that the class throws an exception when an invalid xPath has been given.
     *
     * @dataProvider invalidValuesProvider
     *
     * @param mixed $xPath
     */
    public function testInvalid($xPath)
    {
        $this->expectException(NotValidXPathElement::class);
        new XPathNumber($xPath);
    }

    /**
     * Test if the representation of the given xPath remains the same.
     *
     * @dataProvider basicValuesProvider
     *
     * @param mixed $xPath
     */
    public function testToString($xPath)
    {
        $obj = new XPathNumber($xPath);
        $this->assertSame((string) $xPath, $obj->toString());
    }

    /**
     * Test if the representation of the given xPath remains the same when casting.
     *
     * @dataProvider basicValuesProvider
     *
     * @param mixed $xPath
     */
    public function testToStringCast($xPath)
    {
        $obj = new XPathNumber($xPath);
        $this->assertSame((string) $xPath, (string) $obj);
    }

    /**
     * Test that the method setDefaultNamespacePrefix is useless in this context.
     */
    public function testUselessSetDefaultNamespacePrefix()
    {
        $obj = new XPathNumber(2);
        $obj2 = new XPathNumber(2);
        $obj2->setDefaultNamespacePrefix('something');
        $this->assertEquals($obj, $obj2);
    }

    /**
     * Test that the method setVariableValues is useless in this context.
     */
    public function testUselessSetVariableValues()
    {
        $obj = new XPathNumber(2);
        $obj2 = new XPathNumber(2);
        $obj2->setVariableValues(['something' => 'somethingElse']);
        $this->assertEquals($obj, $obj2);
    }

    /**
     * Tests the evaluation of a number returns the xPath given.
     *
     * @dataProvider basicValuesProvider
     *
     * @param mixed $xPath
     */
    public function testEvaluate($xPath)
    {
        $document = new DOMDocument();

        $obj = new XPathNumber($xPath);
        $this->assertEquals($xPath, $obj->evaluate($document), '', 0.0000001);
    }

    /**
     * Test that the class accepts NaN and can evaluate to a NaN.
     */
    public function testNan()
    {
        $document = new DOMDocument();

        $obj = new XPathNumber('NaN');
        $this->assertNan($obj->evaluate($document));
    }

    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function basicValuesProvider()
    {
        return [
            [0],
            [1],
            [-7],
            [14],
            [-14],
            [78.1847],
            [-45.7352],
            [9865674.9786776658],
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
            [' 0'],
            ['1 '],
            ['12 68'],
            ['jvgku456hbkb'],
            ['NaN '],
        ];
    }
}
