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

class XPathNumber implements ExpressionInterface
{
    protected $number;

    /**
     * {@inheritdoc}
     */
    public function __construct($xPath)
    {
        $this->parse($xPath);
    }

    public function parse($xPath)
    {
        // Check is it is a number
        if (!preg_match('#^-?\d+(.\d+)?$#', $xPath) && $xPath != 'NaN') {
            throw new Exception\NotXPathNumber;
        }

        $this->setNumber($xPath);
    }

    public function toString()
    {
        return (string) $this->getNumber();
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        return;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function setVariableValues(array $values)
    {
        return;
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        return $this->getNumber();
    }
}
