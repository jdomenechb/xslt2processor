<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath;

use DOMNode;
use DOMXPath;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

/**
 * Interface to define the basic methods needed for an xPath expression.
 *
 * @author jdomenechb
 */
interface ExpressionInterface
{
    /**
     * Parses the given xPath to an object representation of the element.
     *
     * @param string $xPath
     *
     * @return bool false if the xPath is not an element of this type
     */
    public function parse($xPath);

    /**
     * Returns the xPath representation of the object.
     *
     * @return string
     */
    public function toString();


    /**
     * Evaluates an expression and returns a result. For now, it is needed normal XSLT still.
     *
     * @param DOMNode  $context
     * @param DOMXPath $xPathReference
     * @returns mixed
     */
    public function evaluate($context);

    /**
     * Performs a query evaluation on the xPath.
     *
     * @param DOMNode $context
     * @returns \Jdomenechb\XSLT2Processor\XML\DOMNodeList
     */
    public function query($context);

    public function setGlobalContext(GlobalContext $context);

    public function setTemplateContext(TemplateContext $context);

    public function getGlobalContext();

    public function getTemplateContext();
}
