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

/**
 * Entity to define the output of the XML/HTML file.
 *
 * @author jdomenechb
 */
class Output
{
    const METHOD_XML = 'xml';
    const METHOD_HTML = 'html';

    /**
     * If true, the XML declaration at the start of the file will be removed.
     *
     * @var bool
     */
    protected $removeXmlDeclaration = false;

    /**
     * Defines the method of output: xml or html.
     *
     * @var string
     */
    protected $method = 'xml';

    /**
     * List of elements that must be CDATA.
     *
     * @var array
     */
    protected $cdataSectionElements = [];

    /**
     * If true, the XML declaration at the start of the file will be removed.
     *
     * @return bool
     */
    public function getRemoveXmlDeclaration()
    {
        return $this->removeXmlDeclaration;
    }

    /**
     * If true, the XML declaration at the start of the file will be removed.
     *
     * @param bool $removeXmlDeclaration
     */
    public function setRemoveXmlDeclaration($removeXmlDeclaration)
    {
        $this->removeXmlDeclaration = $removeXmlDeclaration;
    }

    /**
     * Defines the method of output: xml or html.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Defines the method of output: xml or html.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Gets the list of elements that must be CDATA.
     *
     * @return array
     */
    public function getCdataSectionElements()
    {
        return $this->cdataSectionElements;
    }

    /**
     * Sets the list of elements that must be CDATA.
     *
     * @param type $cdataSectionElements
     */
    public function setCdataSectionElements($cdataSectionElements)
    {
        $this->cdataSectionElements = $cdataSectionElements;
    }
}
