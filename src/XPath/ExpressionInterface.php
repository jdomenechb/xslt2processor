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
     * Defines the namespace prefix that will be used for elements that do not have any.
     *
     * @param string $prefix
     */
    public function setDefaultNamespacePrefix($prefix);

    /**
     * Receive the value of the current variable scope.
     *
     * @param mixed[] $values
     */
    public function setVariableValues(array $values);

    /**
     * Evaluates an expression and returns a result. For now, it is needed normal XSLT still.
     *
     * @param DOMNode  $context
     * @param DOMXPath $xPathReference
     * @returns mixed
     */
    public function evaluate(DOMNode $context, DOMXPath $xPathReference);
}
