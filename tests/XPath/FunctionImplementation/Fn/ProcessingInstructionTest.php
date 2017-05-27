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
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\ProcessingInstruction;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XPath\XPathPath;
use Jdomenechb\XSLT2Processor\XPath\XPathString;
use PHPUnit\Framework\TestCase;

/**
 * Tests the XPath processing-instruction() function.
 *
 * @author jdomenechb
 */
class ProcessingInstructionTest extends TestCase
{
    protected $xml;

    /**
     * Prepare a test XML context.
     */
    protected function setUp()
    {
        parent::setUp();

        $xmlSource = <<<'SOURCE'
<?xml version="1.0"?>
<?xml-stylesheet href="style.css" type="text/css"?>
<?xml-stylesheet href="style2.css" type="text/css"?> 
<root></root>
SOURCE;

        $xml = new \DOMDocument();
        $xml->loadXML($xmlSource);

        $this->xml = $xml;
    }


    /**
     * Tests that the function retrieves the processing instructions in the case there are two of them.
     */
    public function testValidResultExisting()
    {
        $func = new XPathFunction();
        $func->setName('processing-instruction');
        $func->setNamespacePrefix(XPathFunction::DEFAULT_NAMESPACE);

        $parameter = new XPathString();
        $parameter->setString('xml-stylesheet');

        $func->setParameters([$parameter]);

        $funcImpl = new ProcessingInstruction();
        $processingInstructions = $funcImpl->evaluate($func, $this->xml);

        $this->assertCount(2, $processingInstructions);
        $this->assertSame('xml-stylesheet', $processingInstructions->item(0)->target);
        $this->assertSame('href="style.css" type="text/css"', $processingInstructions->item(0)->data);
        $this->assertSame('xml-stylesheet', $processingInstructions->item(1)->target);
        $this->assertSame('href="style2.css" type="text/css"', $processingInstructions->item(1)->data);
    }

    /**
     * Tests that the function does not retrieve any processing instructions in the case they not exist.
     */
    public function testValidResultWithTwo()
    {
        $func = new XPathFunction();
        $func->setName('processing-instruction');
        $func->setNamespacePrefix(XPathFunction::DEFAULT_NAMESPACE);

        $parameter = new XPathString();
        $parameter->setString('xml-style');

        $func->setParameters([$parameter]);

        $funcImpl = new ProcessingInstruction();
        $processingInstructions = $funcImpl->evaluate($func, $this->xml);

        $this->assertCount(0, $processingInstructions);
    }

}
