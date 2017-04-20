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

use Jdomenechb\XSLT2Processor\XPath\ExpressionInterface;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XPath\XPathPath;
use PHPUnit\Framework\TestCase;

/**
 * Tests the XPath count() function.
 *
 * @author jdomenechb
 */
class Count extends TestCase
{
    protected $xml;

    /**
     * Prepare a test XML context.
     */
    protected function setUp()
    {
        parent::setUpBeforeClass();

        $xmlSource = <<<'SOURCE'
<?xml version="1.0"?>
<root><!--I am a comment--><a>contentA</a><b testAttribute="test value attr"><![CDATA[I am a CDATA text section!]]></b><xfoo:c xmlns:xfoo="http://www.example.com/XFoo">A namespaced element</xfoo:c></root>
SOURCE;

        $xml = new \DOMDocument();
        $xml->loadXML($xmlSource);

        $this->xml = $xml;
    }

    /**
     * Tests that the count works correctly for every case.
     *
     * @param $parameter
     * @param mixed $expectedCount
     * @dataProvider dataProvider
     */
    public function testValidResult(ExpressionInterface $parameter, $expectedCount)
    {
        $func = new XPathFunction();
        $func->setParameters([$parameter]);

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Count();
        $count = $funcImpl->evaluate($func, $this->xml);

        $this->assertSame($count, $expectedCount);
    }

    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function dataProvider()
    {
        return [
            [XPathPath::parseXPath('/*'), 1],
            [XPathPath::parseXPath('/*/*'), 3],
            [XPathPath::parseXPath('/*/node()'), 4],
            [XPathFunction::parseXPath('false()'), 1],
            [XPathPath::parseXPath('/*/meh'), 0],
        ];
    }
}
