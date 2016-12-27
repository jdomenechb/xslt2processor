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
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

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
        $eph = new Expression\ExpressionParserHelper();

        $parts = $eph->parseFirstLevelSubExpressions($string, '(', ')');
        array_shift($parts);

        $factory = new Factory();
        $this->setSubExpression($factory->create($parts[0]));
    }

    public function toString()
    {
        return '(' . $this->getSubExpression()->toString() . ')';
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

        return $result;
    }

    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);


        $this->getSubExpression()->setGlobalContext($context);
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);


        $this->getSubExpression()->setTemplateContext($context);
    }
}
