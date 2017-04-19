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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

class XPathVariable extends AbstractXPath
{
    /**
     * Name of the variable.
     *
     * @var string
     */
    protected $name;

    /**
     * Value of the variable.
     *
     * @var mixed
     */
    protected $value;

    public static function parseXPath($string)
    {
        if (!preg_match('#^\$[a-z0-9_-]+$#i', $string)) {
            return false;
        }

        $obj = new self();
        $obj->setName(substr($string, 1));

        return $obj;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function toString()
    {
        return '$' . $this->getName();
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

    /**
     * {@inheritdoc}
     *
     * @param \DOMNode $context
     *
     * @return DOMNodeList|mixed
     */
    public function evaluate($context)
    {
        return $this->getValue();
    }

    /**
     * {@inheritdoc}
     *
     * @param \DOMNode $context
     *
     * @return mixed
     */
    public function query($context)
    {
        if ($this->getValue() instanceof DOMNodeList) {
            return $this->getValue();
        }

        return new DOMNodeList();
    }

    /**
     * {@inheritdoc}
     *
     * @param TemplateContext $context
     */
    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        if (isset($context->getVariables()[$this->getName()])) {
            $this->setValue($context->getVariables()[$this->getName()]);
        }
    }
}
