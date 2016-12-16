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

class Template
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $match = '';

    /**
     * @var \DOMElement
     */
    protected $node;

    /**
     * Priority of the template.
     * @var float
     */
    protected $priority;

    protected $mode;

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
     * @return string
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * @param string $match
     */
    public function setMatch($match)
    {
        $this->match = $match;
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

    /**
     * Returns the priority of the template.
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the priotity of the template.
     * @param float $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }


}
