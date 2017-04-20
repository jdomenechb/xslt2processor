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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\AbstractXPath;
use Jdomenechb\XSLT2Processor\XPath\Exception\ParameterNotValid;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XPath\XPathNumber;
use Jdomenechb\XSLT2Processor\XPath\XPathPath;
use Jdomenechb\XSLT2Processor\XPath\XPathString;
use PHPUnit\Framework\TestCase;

/**
 * Tests the XPath node-name() function.
 *
 * @author jdomenechb
 */
class NodeName extends TestCase
{
    protected $xml;

    /**
     * Prepare a test XML context.
     */
    protected function setUp()
    {
        parent::setUpBeforeClass();

        $xml = new \DOMDocument();

        $root = $xml->createElement('root');
        $xml->appendChild($root);

        $root->appendChild($xml->createComment('I am a comment'));
        $root->appendChild($xml->createElement('a', 'contentA'));

        $b = $xml->createElement('b');
        $b->setAttribute('testAttribute', 'test value attr');
        $root->appendChild($b);

        $b->appendChild($xml->createCDATASection('I am a CDATA text section!'));

        $root->appendChild($xml->createElementNS('http://www.example.com/XFoo', 'xfoo:c', 'A namespaced element'));

        $this->xml = $xml;
    }

    /**
     * Tests that we throw an exception in case the parameter provided is invalid.
     *
     * @param $invalidParameter
     * @dataProvider invalidParameterProvider
     */
    public function testNotValidParameter(AbstractXPath $invalidParameter)
    {
        $this->expectException(ParameterNotValid::class);

        $func = new XPathFunction();
        $func->setParameters([$invalidParameter]);

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\NodeName();
        $funcImpl->evaluate($func, $this->xml);
    }

    /**
     * Tests that the empty sequence is returned when the element is not named.
     *
     * @param $parameter
     * @dataProvider notNamedParameterProvider
     */
    public function testNotNamedParameter($parameter)
    {
        $func = new XPathFunction();
        $func->setParameters([$parameter]);

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\NodeName();
        $result = $funcImpl->evaluate($func, $this->xml);

        $this->assertEquals(new DOMNodeList(), $result);
    }

    /**
     * Tests that the correct name is returned when the element is named.
     *
     * @param $parameter
     * @param $expected
     * @dataProvider namedParameterProvider
     */
    public function testNamedParameter($parameter, $expected)
    {
        $func = new XPathFunction();
        $func->setParameters([$parameter]);
        $func->getGlobalContext()->getNamespaces()['xfoo'] = 'http://www.example.com/XFoo';

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\NodeName();
        $result = $funcImpl->evaluate($func, $this->xml);

        $this->assertSame($expected, $result);
    }

    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function invalidParameterProvider()
    {
        return [
            [XPathString::parseXPath('"test"')],
            [XPathNumber::parseXPath(4)],
            [XPathPath::parseXPath('/*/*')],
        ];
    }

    public function notNamedParameterProvider()
    {
        return [
            [XPathPath::parseXPath('/')],
            [XPathPath::parseXPath('/*/comment()')],
            [XPathPath::parseXPath('/*/a/text()')],
            [XPathPath::parseXPath('/*/b/text()')],
            [XPathPath::parseXPath('/nonExistingElement')],
        ];
    }

    public function namedParameterProvider()
    {
        return [
            [XPathPath::parseXPath('/*'), 'root'],
            [XPathPath::parseXPath('/*/a'), 'a'],
            [XPathPath::parseXPath('/*/b'), 'b'],
            [XPathPath::parseXPath('/*/b/@*'), 'testAttribute'],
            [XPathPath::parseXPath('/*/xfoo:c'), 'xfoo:c'],
        ];
    }
}
