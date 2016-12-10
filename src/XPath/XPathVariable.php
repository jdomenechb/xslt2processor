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

class XPathVariable extends AbstractXPath
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * {@inheritdoc}
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

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
            return $this->getValue() ? 'true()' : 'false()';
        }

        if (is_string($this->getValue())) {
            // FIXME: Escape the string
            return "'" . $this->getValue() . "'";
        }

        if (is_int($this->getValue()) || is_float($this->getValue())) {
            return $this->getValue();
        }

        if ($this->getValue() instanceof \DOMNodeList) {
            return '$' . $this->getName();
        }

        var_dump($this->getValue());
        die;
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
