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

class XPathSub extends AbstractXPath
{
    /**
     * @var ExpressionInterface
     */
    protected $subExpression;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string
     */
    protected $selector;

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
        $eph = new Expression\ExpressionParserHelper();

        $parts = $eph->parseFirstLevelSubExpressions($string, '(', ')');
        array_shift($parts);

        $factory = new Factory();
        $this->setSubExpression($factory->create($parts[0]));

        if (isset($parts[1]) && $parts[1] != '') {
            $subParts = $eph->parseFirstLevelSubExpressions($parts[1], '[', ']');
            $subPartsCount = count($subParts);

            for ($i = 1; $i < $subPartsCount; $i += 2) {
                if (preg_match('#^\d+$#', $subParts[$i])) {
                    $this->setPosition($subParts[$i]);
                } else {
                    $this->setSelector($factory->create($subParts[$i]));
                }
            }
        }
    }

    public function toString()
    {
        return '(' . $this->getSubExpression()->toString() . ')'
            . (!is_null($this->getPosition()) ? '[' . $this->getPosition() . ']' : '')
            . (!is_null($this->getSelector()) ? '[' . $this->getSelector()->toString() . ']' : '');
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

    public function evaluate($context)
    {
        //$xPath = $this->toString();

        $result = $this->getSubExpression()->evaluate($context);

        if (!is_null($this->getSelector())) {
            $newResult = new \Jdomenechb\XSLT2Processor\XML\DOMNodeList();

            foreach ($result as $resultElement) {
                if ($this->getSelector()->evaluate($resultElement)) {
                    $newResult[] = $resultElement;
                }
            }

            $result = $newResult;
        }

        if (!is_null($this->getPosition()) && isset($result[$this->getPosition() - 1])) {
            $result = new \Jdomenechb\XSLT2Processor\XML\DOMNodeList($result[$this->getPosition() - 1]);
        }

        return $result;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
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

        if (!is_null($this->getSelector())) {
            $this->getSelector()->setGlobalContext($context);
        }

        $this->getSubExpression()->setGlobalContext($context);
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        if (!is_null($this->getSelector())) {
            $this->getSelector()->setTemplateContext($context);
        }

        $this->getSubExpression()->setTemplateContext($context);
    }
}
