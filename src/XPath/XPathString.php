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
use Jdomenechb\XSLT2Processor\XPath\Exception\NotXPathString;

/**
 * Represents a xPath string.
 * @author jdomenechb
 */
class XPathString extends AbstractXPath
{
    /**
     *
     * @var string
     */
    protected $string;

    /**
     * {@inheritdoc}
     *
     * The given xPath must be enclosed in simple quotes to be considered an string.
     */
    public function parse($xPath)
    {
        $eph = new Expression\ExpressionParserHelper();

        if (
            // Starts with single quote
            substr($xPath, 0, 1) === "'"
            // Ends with single quote
            && substr($xPath, -1) === "'"
            // Does not contain other quotes inside
            && strpos(substr($eph->subExpressionLevelAnalysis($xPath, "'", "'"), 1, -1), '0') === false
        ) {
            return false;
        }

        $this->string = substr($xPath, 1, -1);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return "'" . $this->getString() . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultNamespacePrefix($prefix)
    {
        // The method does nothing in this context
    }

    /**
     * {@inheritdoc}
     */
    public function setVariableValues(array $values)
    {
        // The method does nothing in this context
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(DOMNode $context, DOMXPath $xPathReference)
    {
        return (string) $this->getString();
    }

    /**
     * @return mixed
     */
    public function getString()
    {
        return $this->string;
    }
}
