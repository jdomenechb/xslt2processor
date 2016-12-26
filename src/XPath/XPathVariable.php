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
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

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

    public function evaluate($context)
    {
        if ($this->getValue() instanceof \DOMNodeList) {
            return new DOMNodeList($this->getValue());
        }

        return $this->getValue();
    }

    public function query($context)
    {
        if ($this->getValue() instanceof DOMNodeList) {
            return $this->getValue();
        }

        throw new \RuntimeException('Not implemented yet');
    }

    public function setGlobalContext(GlobalContext $context)
    {
        try {
            parent::setGlobalContext($context);
        } catch (\RuntimeException $e) {}
    }

    public function setTemplateContext(TemplateContext $context)
    {
        try {
            parent::setTemplateContext($context);
        } catch (\RuntimeException $e) {}

        if (isset($context->getVariables()[$this->getName()])) {
            $this->setValue($context->getVariables()[$this->getName()]);
        }
    }
}
