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
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XPath\Expression\ExpressionParserHelper;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

class XPathFor extends AbstractXPath
{
    /**
     * @var XPathVariable
     */
    protected $variable;

    /**
     * @var ExpressionInterface
     */
    protected $in;

    /**
     * @var ExpressionInterface
     */
    protected $to;

    /**
     * @var ExpressionInterface
     */
    protected $return;

    /**
     * {@inheritdoc}
     *
     * @param string $xPath
     */
    public static function parseXPath($xPath)
    {
        if (strpos($xPath, 'for ') !== 0) {
            return false;
        }

        preg_match('#^for\s+(\$[^\s])+\s+in\s+(.+)$#', $xPath, $matches);

        $obj = new self();
        $obj->setVariable(XPathVariable::parseXPath($matches[1]));

        $e = new ExpressionParserHelper();
        $factory = new Factory();

        if (strpos($matches[2], ' to ') !== false) {
            $expressions = $e->explodeRootLevel(' to ', $matches[2]);
            $obj->setIn($factory->create($expressions[0]));

            $expressions = $e->explodeRootLevel(' return ', $expressions[1]);
            $obj->setTo($factory->create($expressions[0]));
            $obj->setReturn($factory->create($expressions[1]));
        } else {
            $expressions = $e->explodeRootLevel(' return ', $matches[2]);
            $obj->setIn($factory->create($expressions[0]));
            $obj->setReturn($factory->create($expressions[1]));
        }

        return $obj;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function toString()
    {
        return 'for ' . $this->getVariable()->toString() . ' in ' . $this->getIn()->toString()
            . ($this->to !== null ? ' to ' . $this->getTo()->toString() : '') . ' return '
            . $this->getReturn()->toString();
    }

    /**
     * {@inheritdoc}
     *
     * @param \DOMNode $context
     */
    public function evaluate($context)
    {
        $doc = null;
        $in = $this->getIn()->evaluate($context);
        $inNodeList = [];

        if ($this->getTo()) {
            $to = $this->getTo()->evaluate($context);

            $inNodeList = range($in, $to);
        } else {
            $inNodeList = $in;
        }

        $result = [];

        foreach ($inNodeList as $inNodeListItem) {
            $this->getTemplateContext()->getVariables()->offsetSet($this->getVariable()->getName(), $inNodeListItem);
            $this->getTemplateContext()->getVariablesDeclaredInContext()->offsetSet(
                $this->getVariable()->getName(),
                $inNodeListItem
            );

            $subResult = $this->getReturn()->evaluate($context);

            if (!$subResult instanceof DOMNodeList && !$subResult instanceof \DOMNode) {
                if ($doc === null) {
                    $doc = $context;

                    if ($doc instanceof DOMNodeList) {
                        $doc = $doc->item(0);
                    } elseif ($doc instanceof DOMResultTree) {
                        $doc = $doc->getBaseNode();
                    }

                    $doc = $doc instanceof \DOMDocument ? $doc : $doc->ownerDocument;
                }

                $subResult = $doc->createTextNode($subResult);
            }

            $result[] = $subResult;
        }

        return new DOMNodeList($result);
    }

    /**
     * @return XPathVariable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param XPathVariable $variable
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;
    }

    /**
     * @return ExpressionInterface
     */
    public function getIn()
    {
        return $this->in;
    }

    /**
     * @param ExpressionInterface $in
     */
    public function setIn($in)
    {
        $this->in = $in;
    }

    /**
     * @return ExpressionInterface
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * @param ExpressionInterface $return
     */
    public function setReturn($return)
    {
        $this->return = $return;
    }

    /**
     * @return ExpressionInterface
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param ExpressionInterface $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * {@inheritdoc}
     *
     * @param GlobalContext $context
     */
    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);

        if ($this->getVariable()) {
            $this->getVariable()->setGlobalContext($context);
        }

        if ($this->getIn()) {
            $this->getIn()->setGlobalContext($context);
        }

        if ($this->getTo()) {
            $this->getTo()->setGlobalContext($context);
        }

        if ($this->getReturn()) {
            $this->getReturn()->setGlobalContext($context);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param TemplateContext $context
     */
    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        if ($this->getVariable()) {
            $this->getVariable()->setTemplateContext($context);
        }

        if ($this->getIn()) {
            $this->getIn()->setTemplateContext($context);
        }

        if ($this->getTo()) {
            $this->getTo()->setTemplateContext($context);
        }

        if ($this->getReturn()) {
            $this->getReturn()->setTemplateContext($context);
        }
    }
}
