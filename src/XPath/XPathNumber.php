<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 11:27
 */

namespace Jdomenechb\XSLT2Processor\XPath;


class XPathNumber implements ExpressionInterface
{
    protected $number;

    public function parse($string)
    {
        $this->setNumber($string);
    }

    public function toString()
    {
        return $this->getNumber();
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        // Nothing to do here
    }

    /**
     * @inheritDoc
     */
    public function __construct($string)
    {
        $this->parse($string);
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
        // Nothing
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        return $this->getNumber();
    }


}