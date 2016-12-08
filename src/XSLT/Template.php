<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 22/09/2016
 * Time: 19:24
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


}