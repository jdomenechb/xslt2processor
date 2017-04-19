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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\XPathPathNode;
use PHPUnit\Framework\TestCase;

/**
 * Test for the XPathPathNode class.
 *
 * @author jdomenechb
 */
class XPathPathNodeTest extends TestCase
{
    /**
     * Test the given nodes are valid.
     *
     * @param $xPath
     * @param $context
     * @param $expected
     *
     * @ @dataProvider validNodesProvider
     */
    public function testValidEvaluation($xPath, $context, $expected)
    {
        $obj = new XPathPathNode($xPath);
        $result = $obj->evaluate($context);

        $this->assertEquals($expected, $result);
    }

    // --- PROVIDERS ---------------------------------------------------------------------------------------------------

    public function validNodesProvider()
    {
        return [
            // DOMDocument should not have parent
            ['..', new \DOMDocument(), new DOMNodeList()],
        ];
    }
}
