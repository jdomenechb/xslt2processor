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

        $pieces = array_map(
            function ($value) {
                if ($value instanceof ExpressionInterface) {
                    return $value;
                }

                $factory = new Factory();

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

    public function setDefaultNamespacePrefix($prefix)
    {
        array_map(
            function (ExpressionInterface $value) use ($prefix) {
                $value->setDefaultNamespacePrefix($prefix);
            },
            $this->getExpressions()
        );
    }

    public function setVariableValues(array $values)
    {
        array_map(
            function (ExpressionInterface $value) use ($values) {
                $value->setVariableValues($values);
            },
            $this->getExpressions()
        );
    }

    abstract protected function getOperator();

    public function setKeys(array $values)
    {
        array_map(
            function (ExpressionInterface $value) use ($values) {
                $value->setKeys($values);
            },
            $this->getExpressions()
        );
    }

    public function setNamespaces(array $values)
    {
        array_map(
            function (ExpressionInterface $value) use ($values) {
                $value->setNamespaces($values);
            },
            $this->getExpressions()
        );
    }
}
