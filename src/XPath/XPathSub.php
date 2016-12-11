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

            for ($i = 1; $i < count($subParts); $i += 2) {
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
            . (!is_null($this->getPosition())? '[' . $this->getPosition() . ']': '')
            . (!is_null($this->getSelector())? '[' . $this->getSelector()->toString() . ']': '');
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

    public function evaluate($context, \DOMXPath $xPathReference)
    {
        $xPath = $this->toString();
        $result = $this->getSubExpression()->evaluate($context, $xPathReference);

        if (!is_null($this->getSelector())) {
            $newResult = new \Jdomenechb\XSLT2Processor\XML\DOMNodeList();

            foreach ($result as $resultElement) {
                if ($this->getSelector()->evaluate($resultElement, $xPathReference)) {
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

    public function getSelector()
    {
        return $this->selector;
    }

    public function setSelector($selector)
    {
        $this->selector = $selector;
    }

    public function setNamespaces(array $namespaces)
    {
        parent::setNamespaces($namespaces);

        if (!is_null($this->getSelector())) {
            $this->getSelector()->setNamespaces($namespaces);
        }

        $this->getSubExpression()->setNamespaces($namespaces);
    }


}
