<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\Tests\XSLT\Processor;

use Jdomenechb\XSLT2Processor\Tests\AbstractTestCase;
use Jdomenechb\XSLT2Processor\XSLT\Processor;

/**
 * Testing of xsl:attribute-set.
 *
 * @author jdomemechb
 */
class XSLAttributeSetTest extends AbstractTestCase
{
    /**
     * Tests that the processor has successfully parsed the attribute sets given on the test files.
     */
    public function testSuccessfulParsing()
    {
        $xslt = new \DOMDocument();
        $xslt->load($this->getXSLStylesheetPath(__METHOD__));

        $xml = new \DOMDocument();

        $processor = new Processor($xslt, $xml);
        $processor->transformXML();

        $expected = [
            'paragraph-style' => [
                'font-decoration' => 'underline',
                'font-size' => '14px',
            ],

            'span-style' => [
                'font-weight' => 'bold',
                'font-size' => '1.1em',
            ],
        ];

        $this->assertSame($expected, $processor->getGlobalContext()->getAttributeSets()->getArrayCopy());
    }
}
