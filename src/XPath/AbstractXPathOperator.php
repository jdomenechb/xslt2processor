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

use Jdomenechb\XSLT2Processor\XPath\Exception\NotXPathOperator;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

abstract class AbstractXPathOperator extends AbstractXPath
{
    /**
     * @var ExpressionInterface
     */
    protected $leftPart;

    /**
     * @var ExpressionInterface
     */
    protected $rightPart;

    /**
     * @var string
     */
    protected $operator;

    /**
     * {@inheritdoc}
     */
    public function __construct($string = null)
    {
        if ($string) {
            $this->parse($string, true);
        }
    }

    public function parse($string, $constructed = false)
    {
        $factory = new Factory();
        $operators = array_keys(static::getOperators());

        foreach ($operators as $operator) {
            if (in_array($operator, ['-', '*'])) {
                $opWithSpaces = [' ' . $operator . ' ', ' ' . $operator, $operator . ' '];
            } else {
                $opWithSpaces = [$operator];
            }

            // Consider possible minus mispellings
            if ($operator === '-') {
                $possibilities = [
                    ')' . $operator,
                    $operator . '(',
                ];

                foreach ($possibilities as $possibility) {
                    if (mb_stripos($string, $possibility) !== false) {
                        throw new \RuntimeException('Parse error: Operator "-" must have an space before at least');
                    }
                }
            }

            $keyFound = false;

            // First do a fast search
            foreach ($opWithSpaces as $key => $opWithSpacesSingle) {
                if (($opPos = mb_stripos($string, $opWithSpacesSingle)) !== false) {
                    $keyFound = $key;
                    break;
                }
            }

            if ($keyFound === false) {
                continue;
            }

            // Consider equal precedence
            if ($operator === '=' && $opPos > 0) {
                $preOp = substr($string, $opPos - 1, 1);

                if ($preOp === '<' || $preOp === '>') {
                    continue;
                }
            }

            $results = $factory->parseByOperator($opWithSpaces[$keyFound], $string);

            if (count($results) == 1) {
                continue;
            }

            if (count($results) > 1) {
                $this->setOperator($operator);
                $this->setLeftPart($factory->create(array_shift($results)));
                $this->setRightPart($factory->create(implode($opWithSpaces[0], $results)));

                return true;
            }

            throw new \RuntimeException('More than one part with operator');
        }

        if (!$constructed) {
            return false;
        }

        throw new NotXPathOperator();
    }

    public function toString()
    {
        return $this->getLeftPart()->toString() . ' ' . $this->getOperator() . ' ' . $this->getRightPart()->toString();
    }

    /**
     * @return ExpressionInterface
     */
    public function getLeftPart()
    {
        return $this->leftPart;
    }

    /**
     * @param ExpressionInterface $leftPart
     */
    public function setLeftPart(ExpressionInterface $leftPart)
    {
        $this->leftPart = $leftPart;
    }

    /**
     * @return ExpressionInterface
     */
    public function getRightPart()
    {
        return $this->rightPart;
    }

    /**
     * @param ExpressionInterface $rightPart
     */
    public function setRightPart(ExpressionInterface $rightPart)
    {
        $this->rightPart = $rightPart;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    public function evaluate($context)
    {
        $func = static::getOperators()[$this->operator];

        return $func(
            $this->getLeftPart()->evaluate($context),
            $this->getRightPart()->evaluate($context)
        );
    }

    public abstract static function getOperators();

    public function setGlobalContext(GlobalContext $context)
    {
        try {
            parent::setGlobalContext($context);
        } catch (\RuntimeException $e) {}

        $this->getLeftPart()->setGlobalContext($context);
        $this->getRightPart()->setGlobalContext($context);
    }

    public function setTemplateContext(TemplateContext $context)
    {
        try {
            parent::setTemplateContext($context);
        } catch (\RuntimeException $e) {}

        $this->getLeftPart()->setTemplateContext($context);
        $this->getRightPart()->setTemplateContext($context);
    }
}
