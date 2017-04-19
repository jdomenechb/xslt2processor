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

use Jdomenechb\XSLT2Processor\XPath\Exception\NotValidXPathElement;
use Jdomenechb\XSLT2Processor\XPath\XPathVariable;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;
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
     * @dataProvider invalidNamesProvider
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
     * @dataProvider validNamesProvider
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
     * @dataProvider validNamesProvider
     *
     * @param mixed $xPath
     */
    public function testName($xPath)
    {
        $obj = new XPathVariable($xPath);
        $this->assertSame(substr($xPath, 1), $obj->getName());
    }

    /**
     * Tests if, given a context, the variable contains the expected value.
     *
     * @dataProvider templateContextProvider
     *
     * @param TemplateContext $context
     */
    public function testTemplateContextContainingVariable(TemplateContext $context)
    {
        $obj = new XPathVariable();
        $obj->setName('test');
        $obj->setTemplateContext($context);

        $this->assertSame($obj->getValue(), $context->getVariables()->offsetGet('test'));
    }

    // TODO: test for var not in template context

    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function validNamesProvider()
    {
        return [
            ['$a'],
            ['$abcde'],
            ['$abc-def'],
            ['$AbC-dEf'],
            ['$AbC_dEf'],
            ['$a1b2C3'],
        ];
    }

    public function invalidNamesProvider()
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

    public function templateContextProvider()
    {
        $tContextA = new TemplateContext();
        $tContextA->setVariables(new \ArrayObject(['test' => 54]));

        $tContextB = new TemplateContext();
        $tContextB->setVariables(new \ArrayObject(['notatest' => 1, 'test' => 30, 'notrealtest' => 3]));

        return [
            [$tContextA],
            [$tContextB],
        ];
    }
}
