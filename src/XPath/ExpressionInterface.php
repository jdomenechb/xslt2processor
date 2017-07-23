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
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
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
     * @return ExpressionInterface|bool false if the xPath is not an element of this type
     */
    public static function parseXPath($xPath);

    /**
     * Returns the xPath representation of the object.
     *
     * @return string
     */
    public function toString();

    /**
     * Evaluates an expression and returns a result.
     *
     * @param DOMNode  $context
     * @returns mixed
     */
    public function evaluate($context);

    /**
     * Performs a query evaluation on the xPath.
     *
     * @param DOMNode|DOMNodeList $context
     * @returns \Jdomenechb\XSLT2Processor\XML\DOMNodeList
     */
    public function query($context);

    public function setGlobalContext(GlobalContext $context);

    public function setTemplateContext(TemplateContext $context);

    public function getGlobalContext();

    public function getTemplateContext();
}
