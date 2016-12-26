<?php

/**
 * This file is part of the XSLT2processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Context;

/**
 * Defines the context for a template, a var body, etc.
 * @author jdomemechb
 */
class TemplateContext
{
    /**
     * @var \ArrayObject
     */
    protected $variables;

    /**
     * @var \ArrayObject
     */
    protected $variablesDeclaredInContext;

    public function __construct()
    {
        $this->variables = new \ArrayObject();
        $this->variablesDeclaredInContext = new \ArrayObject();
    }

    /**
     * @return \ArrayObject
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @param \ArrayObject $variables
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    public function __clone()
    {
        $this->setVariablesDeclaredInContext(new \ArrayObject());
    }

    /**
     * @return \ArrayObject
     */
    public function getVariablesDeclaredInContext()
    {
        return $this->variablesDeclaredInContext;
    }

    /**
     * @param \ArrayObject $variablesDeclaredInContext
     */
    public function setVariablesDeclaredInContext($variablesDeclaredInContext)
    {
        $this->variablesDeclaredInContext = $variablesDeclaredInContext;
    }
}