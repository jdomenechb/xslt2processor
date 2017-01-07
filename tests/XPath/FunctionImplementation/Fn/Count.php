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
     * Tests that the count works correctly for every case.
     * @param $parameter
     * @dataProvider dataProvider
     */
    public function testNotValidParameter(ExpressionInterface $parameter)
    {
        $func = new XPathFunction();
        $func->setParameters([$parameter]);

        $funcImpl = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Count();
        $funcImpl->evaluate($func, $this->xml);
    }


    // --- PROVIDERS ---------------------------------------------------------------------------------------------------


    public function dataProvider()
    {
        return [
            [new XPathPath('/*'), 1],
            [new XPathPath('/*/*'), 3],
            [new XPathPath('/*/node()'), 4],
            [new XPathFunction('false()'), 1],
            [new XPathPath('/*/meh'), 0],
        ];
    }
}