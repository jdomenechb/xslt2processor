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

use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XPath\XPathString;
use PHPUnit\Framework\TestCase;

/**
 * Tests the XPath document() function.
 *
 * @author jdomenechb
 */
class DocumentTest extends TestCase
{
    protected $xml;

    /**
     * Tests the function when it receives an empty string and nothing else: it should return a set of nodes containing
     * the XML document put at stack.
     */
    public function testEmptyStringAndNull()
    {
        // TODO: Replace by method from abstract parent class
        $xml = new \DOMDocument();
        $xml->load('tests/data/' . str_replace(['\\', '::'], '/', __METHOD__) . '-001.xml');

        $func = new XPathFunction();
        $func->setParameters([XPathString::parseXPath('""')]);
        $func->getGlobalContext()->getStylesheetStack()->push($xml);

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Document();
        $result = $funcImpl->evaluate($func, null);

        $this->assertEqualXMLStructure($result->item(0)->documentElement, $xml->documentElement);
    }

    // TODO: Other tests
}
