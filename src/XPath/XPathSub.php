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

class XPathSub extends AbstractXPath
{
    /**
     * @var ExpressionInterface
     */
    protected $subExpression;

    /**
     * XPathSub constructor.
     *
     * @param mixed $string
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    public function parse($string)
    {
        $factory = new Factory();
        $this->setSubExpression($factory->create(substr($string, 1, -1)));
    }

    public function toString()
    {
        return '(' . $this->getSubExpression()->toString() . ')';
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        $this->getSubExpression()->setDefaultNamespacePrefix($prefix);
    }

    public function setVariableValues(array $values)
    {
        $this->getSubExpression()->setVariableValues($values);
    }

    /**
     * @return ExpressionInterface
     */
    public function getSubExpression()
    {
        return $this->subExpression;
    }

    /**
     * @param ExpressionInterface $subExpression
     */
    public function setSubExpression($subExpression)
    {
        $this->subExpression = $subExpression;
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        return $this->getSubExpression()->evaluate($context, $xPathReference);
    }
}
