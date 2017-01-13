<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Context;

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

/**
 * Defines the context for a template, a var body, etc.
 *
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

    /**
     * @var DOMNodeList
     */
    protected $contextParent;

    /**
     * @var DOMNodeList
     */
    protected $group;

    /**
     * @var string
     */
    protected $groupingKey;

    public function __construct()
    {
        $this->variables = new \ArrayObject();
        $this->variablesDeclaredInContext = new \ArrayObject();
    }

    public function __clone()
    {
        $this->setVariablesDeclaredInContext(new \ArrayObject());
        $this->setContextParent(null);
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

    /**
     * @return DOMNodeList
     */
    public function getContextParent()
    {
        return $this->contextParent;
    }

    /**
     * @param DOMNodeList $contextParent
     */
    public function setContextParent(DOMNodeList $contextParent = null)
    {
        $this->contextParent = $contextParent;
    }

    /**
     * @return DOMNodeList
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param DOMNodeList $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getGroupingKey()
    {
        return $this->groupingKey;
    }

    /**
     * @param string $groupingKey
     */
    public function setGroupingKey($groupingKey)
    {
        $this->groupingKey = $groupingKey;
    }
}
