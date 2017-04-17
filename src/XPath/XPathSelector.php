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
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Current;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

class XPathSelector extends AbstractXPath
{
    /**
     * @var ExpressionInterface
     */
    protected $selector;

    /**
     * @var ExpressionInterface
     */
    protected $expression;

    public static function parseXPath($string)
    {
        if (substr($string, -1) !== ']') {
            return false;
        }

        $eph = new Expression\ExpressionParserHelper();
        $parts = $eph->parseFirstLevelSubExpressions($string, '[', ']');

        if (count($parts) <= 1) {
            return false;
        }

        // Remove empty part at the end
        array_pop($parts);
        $selectorString = array_pop($parts);

        $factory = new Factory();
        $obj = new self;

        $obj->setSelector($factory->create($selectorString));

        // Strip the selector from the original string
        $string = substr($string, 0, -strlen($selectorString) - 2);

        $obj->setExpression($factory->create($string));

        return $obj;
    }

    public function toString()
    {
        return $this->getExpression()->toString() . '[' . $this->getSelector()->toString() . ']';
    }

    public function evaluate($context)
    {
        $result = $this->getExpression()->evaluate($context);
        $newResult = new DOMNodeList();

        if ($this->getSelector() instanceof XPathNumber && $result->count()) {
            $newResult[] = $result->item($this->getSelector()->toString() - 1);
        } else {
            foreach ($result as $resultElement) {
                Current::getStack()->push($resultElement);

                $evaluation = $this->getSelector()->evaluate($resultElement);
                if (
                    ($evaluation instanceof DOMNodeList && $evaluation->count())
                    || (!$evaluation instanceof DOMNodeList && $evaluation)
                ) {
                    $newResult[] = $resultElement;
                }

                Current::getStack()->pop();
            }
        }

        $result = $newResult;

        return $result;
    }

    public function query($context)
    {
        return $this->evaluate($context);
    }

    /**
     * @return ExpressionInterface
     */
    public function getSelector()
    {
        return $this->selector;
    }

    public function setSelector($selector)
    {
        $this->selector = $selector;
    }

    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);

        $this->getExpression()->setGlobalContext($context);
        $this->getSelector()->setGlobalContext($context);
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        $this->getExpression()->setTemplateContext($context);
        $this->getSelector()->setTemplateContext($context);
    }

    /**
     * @return ExpressionInterface
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param ExpressionInterface $expression
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;
    }
}
