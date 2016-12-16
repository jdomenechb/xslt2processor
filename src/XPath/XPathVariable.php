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
            return "'" . $this->getValue() . "'";
        }

        if (is_int($this->getValue()) || is_float($this->getValue())) {
            return $this->getValue();
        }

        if (
            $this->getValue() instanceof \DOMNodeList
            || $this->getValue() instanceof \Jdomenechb\XSLT2Processor\XML\DOMNodeList
            || $this->getValue() instanceof \DOMNode
        ) {
            return '$' . $this->getName();
        }

        var_dump($this->getValue());

        throw new \RuntimeException('Variable of type not recognised');
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

    public function evaluate($context)
    {
        if ($this->getValue() instanceof \DOMNodeList) {
            return new \Jdomenechb\XSLT2Processor\XML\DOMNodeList($this->getValue());
        }

        return $this->getValue();
    }

    public function query($context)
    {
        if ($this->getValue() instanceof \Jdomenechb\XSLT2Processor\XML\DOMNodeList) {
            return $this->getValue();
        }

        throw new \RuntimeException('Not implemented yet');
    }

    public function setKeys(array $keys)
    {
        // This method is intended to be left empty
    }
}
