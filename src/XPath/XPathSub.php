<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 12:53
 */

namespace Jdomenechb\XSLT2Processor\XPath;


class XPathSub implements ExpressionInterface
{
    /**
     * @var ExpressionInterface
     */
    protected $subExpression;

    /**
     * XPathSub constructor.
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