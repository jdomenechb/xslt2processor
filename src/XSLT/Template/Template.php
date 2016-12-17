<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Template;

use DOMElement;

/**
 * This class represents an available xsl:template that can be find in the stylesheet.
 *
 * @author jdomenechb
 */
class Template
{
    /**
     * Name of the template (optional).
     *
     * @var string
     */
    protected $name = '';

    /**
     * Match of the template (optional).
     *
     * @var string
     */
    protected $match = '';

    /**
     * Pointer to the real DOMElement node in the XSL DOMs.
     *
     * @var DOMElement
     */
    protected $node;

    /**
     * Priority of the template.
     *
     * @var float
     */
    protected $priority;

    /**
     * Mode of the template (optional).
     *
     * @var string
     */
    protected $mode;

    // TODO: Add a constructor that can parse most of the attributes

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
     * @return DOMElement
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param DOMElement $node
     */
    public function setNode($node)
    {
        $this->node = $node;
    }

    /**
     * Returns the priority of the template.
     *
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the priotity of the template.
     *
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

    /**
     * Set the mode of the template.
     *
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }
}
