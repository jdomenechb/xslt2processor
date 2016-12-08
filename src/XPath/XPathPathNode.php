<?php

namespace Jdomenechb\XSLT2Processor\XPath;

class XPathPathNode implements ExpressionInterface
{
    /**
     * @var string
     */
    protected $node;

    /**
     *
     * @var int
     */
    protected $position;

    /**
     *
     * @var ExpressionInterface
     */
    protected $selector;

    /**
     * @inheritDoc
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    public function parse($string)
    {
        $factory = new Factory();

        if (strpos($string, '[') === false) {
            $this->setNode($string);
            return;
        }

        $pieces = $factory->parseByLevel($string, '[', ']');
        $this->setNode(array_shift($pieces));

        foreach ($pieces as $piece) {
            if (is_numeric($piece)) {
                $this->setPosition($piece);
                continue;
            }

            $this->setSelector($factory->create($piece));
        }
    }

    public function toString()
    {
        return $this->getNode()
            . ($this->getSelector() !== null? '[' . $this->getSelector()->toString() . ']' : '')
            . ($this->getPosition() !== null? '[' . $this->getPosition() . ']' : '');
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        if (
            trim($this->getNode()) == ''
            || strpos(trim($this->getNode()), '*') === 0
            || strpos(trim($this->getNode()), '.') === 0
        ) {
            return;
        }

        $parts = explode(':', $this->getNode());

        if (count($parts) == 1) {
            $toSet =  $prefix . ':' . $parts[0];
        } else {
            $toSet = $this->getNode();
        }

        $this->setNode($toSet);
    }

    public function setVariableValues(array $values)
    {
        // Nothing
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        $value = $xPathReference->evaluate($this->getNode(), $context);

        return $value;
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param string $node
     */
    public function setNode($node)
    {
        $this->node = $node;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getSelector()
    {
        return $this->selector;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setSelector(ExpressionInterface $selector)
    {
        $this->selector = $selector;
    }

}
