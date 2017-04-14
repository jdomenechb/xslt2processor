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
use Jdomenechb\XSLT2Processor\XPath\XPathVariable;
use PHPUnit\Framework\TestCase;

/**
 * Test for the XPathVariable class.
 *
 * @author jdomenechb
 */
class XPathVariableTest extends TestCase
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
        new XPathVariable($xPath);
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
        $obj = new XPathVariable($xPath);
        $this->assertSame((string) $xPath, $obj->toString());
    }

    /**
     * Test if the name of the variable has been correctly parsed.
     *
     * @dataProvider basicValuesProvider
     *
     * @param mixed $xPath
     */
    public function testName($xPath)
    {
        $obj = new XPathVariable($xPath);
        $this->assertSame(substr($xPath, 1), $obj->getName());
    }


    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function basicValuesProvider()
    {
        return [
            ['$a', 'a'],
            ['$abcde', 'abcde'],
            ['$abc-def', 'abc def'],
            ['$AbC-dEf', "abc ' def"],
            ['$AbC_dEf', "abc ' def"],
            ['$a1b2C3', 'a'],
        ];
    }

    public function invalidValuesProvider()
    {
        return [
            ['withoutDollar'],
            ['dollar$Inside'],
            [' $withInitSpace'],
            ['$withTrailingSpace '],
            ['$.withNotAllowedSymbol1'],
            ['with,NotAllowedSymbol2'],
            ['withDollarAtEnd$'],
            ['$'],
        ];
    }
}
