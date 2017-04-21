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
class Document extends TestCase
{
    protected $xml;

    /**
     * Tests the function when it receives an empty string and nothing else: it should return a set of nodes containing
     * the the document.
     */
    public function testEmptyStringAndNull()
    {
        $xml = new \DOMDocument();
        $xml->load('tests/data/' . str_replace(['\\', '::'], '/', __METHOD__) . '-001.xml');
        
        $func = new XPathFunction();
        $func->setParameters([XPathString::parseXPath('""')]);

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Document();
        $result = $funcImpl->evaluate($func, $xml);

        $this->assertEqualXMLStructure($result->item(0)->documentElement, $xml->documentElement);
    }

    // TODO: Other tests
}
