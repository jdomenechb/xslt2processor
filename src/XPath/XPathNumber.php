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

use Jdomenechb\XSLT2Processor\XPath\Exception\InvalidEvaluation;

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
    public function parse($xPath)
    {
        $xPath = (string) $xPath;

        // Check if it is a number
        if (!preg_match('#^(?:-?\d+(?:\.\d+)?|NaN)$#', $xPath)) {
            return false;
        }

        $this->number = $xPath;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate($context)
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

    public function toString()
    {
        return (string) $this->getNumber();
    }
}
