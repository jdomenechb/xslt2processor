<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 05/10/2016
 * Time: 12:20
 */

namespace Jdomenechb\XSLT2Processor\XSLT;


class CustomFunction  {
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var Processor
     */
    protected $context;

    /**
     * @var \DOMElement
     */
    protected $node;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Processor
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Processor $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return \DOMElement
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param \DOMElement $node
     */
    public function setNode($node)
    {
        $this->node = $node;
    }
}