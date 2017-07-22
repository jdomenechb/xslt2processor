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

use Jdomenechb\XSLT2Processor\XPath\Expression\ExpressionParserHelper;
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

    public static function parseXPath($string)
    {
        $factory = new Factory();
        $eph = new ExpressionParserHelper();

        foreach (static::getOperators() as $operator => $nothing) {
            // Guess the possibility of having the operator
            if (strpos($string, $operator) === false) {
                continue;
            }

            // Consider possible minus misspellings
            if ($operator === '-') {
                $possibilities = [
                    ')' . $operator,
                    $operator . '(',
                ];

                foreach ($possibilities as $possibility) {
                    if (mb_stripos($string, $possibility) !== false) {
                        throw new \ParseError('Operator "-" must have an space before at least');
                    }
                }
            }

            // Prepare the possible cases for fast search
            if (in_array($operator, ['-', 'mod', 'div'])) {
                // These operators can only have space before, after, and both
                $opWithSpaces = [' ' . $operator . ' ', ' ' . $operator, $operator . ' '];
            } elseif (in_array($operator, ['*'])) {
                // These operators can only have space before, both before & after
                $opWithSpaces = [' ' . $operator . ' ', ' ' . $operator];
            } else {
                $opWithSpaces = [$operator];
            }

            $keyFound = false;
            $opPos = false;

            // First do a fast search to determine the real operator we are dealing with
            foreach ($opWithSpaces as $key => $opWithSpacesSingle) {
                if (($opPos = stripos($string, $opWithSpacesSingle)) !== false) {
                    $keyFound = $key;
                    break;
                }
            }

            if ($keyFound === false) {
                continue;
            }

            // Consider equal precedence against gt and lt
            if ($operator === '=' && $opPos > 0) {
                $preOp = $string[$opPos - 1];

                if ($preOp === '<' || $preOp === '>') {
                    continue;
                }
            }

            // Parse using the detected operator
            $results = $eph->explodeRootLevel($opWithSpaces[$keyFound], $string);

            if (count($results) === 1) {
                continue;
            }

            if (count($results) > 1) {
                /** @var AbstractXPathOperator $obj */
                $obj = get_called_class();
                $obj = new $obj();

                $obj->setOperator($operator);
                $obj->setRightPart($factory->create(array_pop($results)));
                $obj->setLeftPart($factory->create(implode($opWithSpaces[0], $results)));

                return $obj;
            }

            throw new \RuntimeException('More than one part with operator');
        }

        return false;
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

    protected function evaluateExpression ($context)
    {
        $func = static::getOperators()[$this->operator];

        $leftPart = $this->getLeftPart()->evaluate($context);
        $rightPart = $this->getRightPart()->evaluate($context);

        return $func($leftPart, $rightPart);
    }

    /**
     * @return array
     */
    abstract public static function getOperators();

    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);

        $this->getLeftPart()->setGlobalContext($context);
        $this->getRightPart()->setGlobalContext($context);
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        $this->getLeftPart()->setTemplateContext($context);
        $this->getRightPart()->setTemplateContext($context);
    }
}
