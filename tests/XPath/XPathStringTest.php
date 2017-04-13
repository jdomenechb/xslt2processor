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
use Jdomenechb\XSLT2Processor\XPath\Exception\InvalidEvaluation;
use Jdomenechb\XSLT2Processor\XPath\Exception\NotValidXPathElement;
use Jdomenechb\XSLT2Processor\XPath\XPathString;
use PHPUnit\Framework\TestCase;

/**
 * Test for the XPathString class.
 *
 * @author jdomenechb
 */
class XPathStringTest extends TestCase
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
        new XPathString($xPath);
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
        $obj = new XPathString($xPath);
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
        $obj = new XPathString($xPath);
        $this->assertSame((string) $xPath, (string) $obj);
    }

    /**
     * Tests the evaluation of a string returns the xPath given.
     *
     * @dataProvider basicValuesProvider
     *
     * @param mixed $xPath
     * @param mixed $evaluated
     */
    public function testEvaluate($xPath, $evaluated)
    {
        $document = new DOMDocument();

        $obj = new XPathString($xPath);
        $this->assertSame($evaluated, $obj->evaluate($document));
    }

    /**
     * Tests the evaluation correctly throws an InvalidEvaluation exception.
     */
    public function testInvalidEvaluate()
    {
        $this->expectException(InvalidEvaluation::class);
        $document = new DOMDocument();

        $obj = new XPathString();
        $obj->evaluate($document);
    }

    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function basicValuesProvider()
    {
        return [
            ["'a'", 'a'],
            ["'abcde'", 'abcde'],
            ["'abc def'", 'abc def'],
            ["'abc '' def'", "abc ' def"],
            ['"a"', 'a'],
            ['"abcde"', 'abcde'],
            ['"abc def"', 'abc def'],
            ['"abc "" def"', 'abc " def'],
        ];
    }

    public function invalidValuesProvider()
    {
        return [
            ["'abcde"],
            ["abcde'"],
            ['abcde'],
            ['98'],
            ["'98 times of year ' '"],
            ['(/*)'],
            ['/*'],
            ["'A valid string' != 'Another valid string'"],
        ];
    }
}
