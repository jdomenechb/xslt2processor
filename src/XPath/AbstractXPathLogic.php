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

use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

abstract class AbstractXPathLogic extends AbstractXPath
{
    /**
     * @var array
     */
    protected $expressions;

    /**
     * {@inheritdoc}
     */
    public function __construct($pieces)
    {
        $this->parse($pieces);
    }

    public function parse($pieces)
    {
        if (!is_array($pieces)) {
            $pieces = [$pieces];
        }

        $factory = new Factory();

        $pieces = array_map(
            function ($value) use ($factory) {
                if ($value instanceof ExpressionInterface) {
                    return $value;
                }

                return $factory->create($value);
            },
            $pieces
        );

        $this->setExpressions($pieces);
    }

    public function toString()
    {
        $pieces = array_map(function ($value) {
            return $value->toString();
        }, $this->getExpressions());

        return implode(' ' . $this->getOperator() . ' ', $pieces);
    }

    /**
     * @param array $expressions
     */
    public function setExpressions($expressions)
    {
        $this->expressions = $expressions;
    }

    /**
     * @return array
     */
    public function getExpressions()
    {
        return $this->expressions;
    }

    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);

        array_map(
            function (ExpressionInterface $value) use ($context) {
                $value->setGlobalContext($context);
            },
            $this->getExpressions()
        );
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        array_map(
            function (ExpressionInterface $value) use ($context) {
                $value->setTemplateContext($context);
            },
            $this->getExpressions()
        );
    }

    abstract protected function getOperator();
}
