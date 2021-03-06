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

use DOMElement;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

class XPathEveryLevelPath extends AbstractXPath
{
    /**
     * @var ExpressionInterface
     */
    protected $leftPart;

    /**
     * @var ExpressionInterface
     */
    protected $rightPart;

    public static function parseXPath($string)
    {
        $eph = new Expression\ExpressionParserHelper();
        $parts = $eph->explodeRootLevel('//', $string);

        if (count($parts) <= 1) {
            return false;
        }

        $factory = new Factory();
        $obj = new self();

        $obj->setLeftPart($factory->create(array_shift($parts)));
        $obj->setRightPart($factory->create(implode('//', $parts)));

        return $obj;
    }

    public function toString()
    {
        return $this->getLeftPart()->toString() . '//' . $this->getRightPart()->toString();
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
     * {@inheritdoc}
     *
     * @param \DOMNode $context
     *
     * @return DOMNodeList
     */
    protected function evaluateExpression ($context)
    {
        $evaluation = $this->getLeftPart()->evaluate($context);

        if (!$evaluation instanceof DOMNodeList) {
            throw new \RuntimeException('Left part of ' . static::class . ' is not a list of nodes');
        }

        $results = new DOMNodeList();

        foreach ($evaluation as $node) {
            $this->deepEvaluation($results, $node);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param \DOMNode $context
     *
     * @return DOMNodeList|mixed
     */
    public function query($context)
    {
        return $this->evaluate($context);
    }

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

    protected function deepEvaluation(DOMNodeList $results, \DOMNode $node)
    {
        $results->merge($this->getRightPart()->evaluate($node));

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            $this->deepEvaluation($results, $childNode);
        }
    }
}
