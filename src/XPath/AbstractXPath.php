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

use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Current;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

/**
 * Abstract class for implementing an XPath element.
 *
 * @author jdomenechb
 */
abstract class AbstractXPath implements ExpressionInterface
{
    protected $namespaces;
    protected $globalContext;
    protected $templateContext;

    /**
     * Returns the xPath representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function query($context)
    {
        throw new \RuntimeException('Not implemented yet in ' . get_called_class());
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function setGlobalContext(GlobalContext $context)
    {
        $this->globalContext = $context;
    }

    public function setTemplateContext(TemplateContext $context)
    {
        $this->templateContext = $context;
    }

    /**
     * @return GlobalContext
     */
    public function getGlobalContext()
    {
        if (!$this->globalContext) {
            $this->setGlobalContext(new GlobalContext());
        }

        return $this->globalContext;
    }

    /**
     * @return TemplateContext
     */
    public function getTemplateContext()
    {
        return $this->templateContext;
    }

    /**
     * Evaluates an expression and returns a result.
     *
     * @param \DOMNode  $context
     * @returns mixed
     */
    abstract protected function evaluateExpression ($context);

    /**
     * @inheritdoc
     */
    public function evaluate($context)
    {
        $stack = Current::getStack();

        if (!$stack->isEmpty()) {
            return $this->evaluateExpression($context);
        }

        $stack->push($context);
        $toReturn = $this->evaluateExpression($context);
        $stack->pop();

        return $toReturn;
    }
}
