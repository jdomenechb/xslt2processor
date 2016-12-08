<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 11:27
 */

namespace Jdomenechb\XSLT2Processor\XPath;


class XPathString implements ExpressionInterface
{
    protected $string;

    public function parse($string)
    {
        $this->setString(substr($string, 1, -1));
    }

    public function toString()
    {
        return "'" . $this->getString() . "'";
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
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param mixed $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    public function setVariableValues(array $values)
    {
        // Nothing
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        return $this->getString();
    }


}