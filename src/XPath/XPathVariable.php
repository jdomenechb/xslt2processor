<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 12:32
 */

namespace Jdomenechb\XSLT2Processor\XPath;


class XPathVariable implements ExpressionInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $value;

    public function parse($string)
    {
        $this->setName(substr($string, 1));
    }

    public function toString()
    {
        if (is_null($this->getValue())) {
            return '$' . $this->getName();
        }

        if (is_bool($this->getValue())) {
            return $this->getValue()? 'true()': 'false()';
        }

        if (is_string($this->getValue())) {
            return "'" . $this->getValue() . "'";
        }

        if (is_integer($this->getValue()) || is_float($this->getValue())) {
            return $this->getValue();
        }

        if ($this->getValue() instanceof \DOMNodeList) {
            return '$' . $this->getName();
        }

        var_dump($this->getValue());die;

    }

    public function setDefaultNamespacePrefix($prefix)
    {
        // Nothing
    }

    public function setVariableValues(array $values)
    {
        if (isset($values[$this->getName()])) {
            $this->setValue($values[$this->getName()]);
        }
    }

    /**
     * @inheritDoc
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        return $this->getValue();
    }


}