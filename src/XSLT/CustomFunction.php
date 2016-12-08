<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT;

class CustomFunction
{
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
