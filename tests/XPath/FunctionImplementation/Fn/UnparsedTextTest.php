<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\Tests\XPath\FunctionImplementation\Fn;

use Jdomenechb\XSLT2Processor\Tests\AbstractTestCase;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;

/**
 * Tests the XPath fn:unparsed-text() function.
 *
 * @author jdomenechb
 */
class UnparsedTextTest extends AbstractTestCase
{
    protected $xml;

    /**
     * Tests that the function van read the file relatively to the file
     */
    public function testValid()
    {
        // Prepare the XSL reference sheet
        $xslPath = $this->getXSLStylesheetPath(__METHOD__);

        $xsl = new \DOMDocument();
        $xsl->load($xslPath);

        // Prepare the global context
        $globalContext = new GlobalContext();
        $globalContext->getStylesheetStack()->push($xsl);

        $func = XPathFunction::parseXPath("unparsed-text('dummy-subfolder/./../dummy-subfolder/content.txt')");
        $func->setGlobalContext($globalContext);
        $result = $func->evaluate(new \DOMDocument());

        $this->assertSame('Content of the file', $result);
    }
}
