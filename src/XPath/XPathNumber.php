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
    public static function parseXPath($xPath)
    {
        $xPath = (string) $xPath;

        // Check if it is a number
        if (!preg_match('#^(?:-?\d+(?:\.\d+)?|NaN)$#', $xPath)) {
            return false;
        }

        $obj = new self;
        $obj->setNumber($xPath);

        return $obj;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidEvaluation
     */
    public function evaluate($context)
    {
        // If it has decimal part, it's a float, otherwise, it's an integer
        if (preg_match('#^-?\d+(\.\d+)?$#', $this->getNumber(), $matches)) {
            return isset($matches[1])? (float) $this->getNumber() : (int) $this->getNumber();
        }

        // NaN
        if ($this->getNumber() === 'NaN') {
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

    /**
     * @inheritdoc
     * @return string
     */
    public function toString()
    {
        return (string) $this->getNumber();
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }
}
