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
use Jdomenechb\XSLT2Processor\XPath\Exception\InvalidEvaluation;
use Jdomenechb\XSLT2Processor\XPath\Exception\NotXPathNumber;

/**
 * Represents a number in an xPath.
 *
 * @author jdomenechb
 */
class XPathNumber extends AbstractXPath
{
    /**
     * @var mixed
     */
    protected $number;

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function parse($xPath)
    {
        $xPath = (string) $xPath;

        // Check if it is a number
        if (!preg_match('#^-?\d+(?:\.\d+)?$#', $xPath) && $xPath != 'NaN') {
            throw new NotXPathNumber();
        }

        $this->number = $xPath;
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
        // Integer
        if (preg_match('#^-?\d+$#', $this->getNumber())) {
            return (int) $this->getNumber();
        }

        // Float
        if (preg_match('#^-?\d+\.\d+$#', $this->getNumber())) {
            return (float) $this->getNumber();
        }

        // NaN
        if ($this->getNumber() == 'NaN') {
            return NAN;
        }

        throw new InvalidEvaluation('Not a valid number evaluation');
    }

    /**
     * Returns the number contained in this object.
     *
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }
}
