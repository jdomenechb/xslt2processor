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
use Jdomenechb\XSLT2Processor\XPath\Factory;

/**
 * Entity to define the output of the XML/HTML file.
 *
 * @author jdomenechb
 */
class Output
{
    const METHOD_XML = 'xml';
    const METHOD_HTML = 'html';
    const METHOD_TEXT = 'text';

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
     * Version of the output that is intended to be used.
     *
     * @var float
     */
    protected $version;

    /**
     * Public attribute in the DOCTYPE declaration.
     *
     * @var string
     */
    protected $doctypePublicAttribute;

    /**
     * System attribute in the DOCTYPE declaration.
     *
     * @var string
     */
    protected $doctypeSystemAttribute;

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
     * @param array $cdataSectionElements
     */
    public function setCdataSectionElements($cdataSectionElements)
    {
        $this->cdataSectionElements = $cdataSectionElements;
    }

    /**
     * Get the version of output intended to use.
     *
     * @return float
     */
    public function getVersion()
    {
        return (float) $this->version;
    }

    /**
     * Set the version of ouptut intended to use.
     *
     * @param float $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get the public attribute in the DOCTYPE declaration.
     *
     * @return string
     */
    public function getDoctypePublicAttribute()
    {
        return $this->doctypePublicAttribute;
    }

    /**
     * Set the public attribute in the DOCTYPE declaration.
     *
     * @param string $doctypePublicAttribute
     */
    public function setDoctypePublicAttribute($doctypePublicAttribute)
    {
        $this->doctypePublicAttribute = $doctypePublicAttribute;
    }

    /**
     * Get the system attribute in the DOCTYPE declaration.
     *
     * @return string
     */
    public function getDoctypeSystemAttribute()
    {
        return $this->doctypeSystemAttribute;
    }

    /**
     * Set the system attribute in the DOCTYPE declaration.
     *
     * @param string $doctypeSystemAttribute
     */
    public function setDoctypeSystemAttribute($doctypeSystemAttribute)
    {
        $this->doctypeSystemAttribute = $doctypeSystemAttribute;
    }

    /**
     * Returns a well-formed DOCTYPE by the information provided to this class.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getDoctype()
    {
        $version = $this->getVersion() ?: 4.0;

        if ($this->getMethod() !== static::METHOD_HTML) {
            throw new \RuntimeException('Non HTML output methods are not supported for DOCTYPE');
        }

        $doctype = '<!DOCTYPE ';

        if ($version === 5.0) {
            return $doctype . 'html>';
        }

        if ($version === 4.0) {
            $doctype .= 'HTML';
        } else {
            $doctype .= 'html';
        }

        if ($this->getDoctypePublicAttribute() === null && $this->getDoctypeSystemAttribute() === null) {
            if ($version === 4.0) {
                return $doctype
                    . ' PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
            }

            throw new \RuntimeException("No default DOCTYPE declared for HTML version $version");
        }

        if ($this->getDoctypePublicAttribute()) {
            $doctype .= ' PUBLIC "' . $this->getDoctypePublicAttribute() . '"';

            if ($this->getDoctypeSystemAttribute()) {
                $doctype .= ' "' . $this->getDoctypePublicAttribute() . '"';
            }
        }

        return $doctype . '>';
    }

    public function getMetaCharsetTag(\DOMDocument $doc)
    {
        if ($this->getMethod() !== static::METHOD_HTML) {
            throw new \RuntimeException('Non HTML output methods are not supported for charset meta');
        }

        $meta = $doc->createElement('meta');

        if ($this->getVersion() === 5) {
            $meta->setAttribute('charset', $doc->encoding);
        } else {
            $meta->setAttribute('http-equiv', 'Content-Type');
            $meta->setAttribute('content', 'text/html; charset=' . $doc->encoding);
        }

        return $meta;
    }

    /**
     * Formats a document following the rules of the Output instance.
     *
     * @param \DOMNode $doc
     * @return string
     */
    public function formatXml(\DOMNode $doc)
    {
        if ($this->getMethod() === static::METHOD_XML) {
            $content = $doc instanceof \DOMDocument ? $doc->saveXML() : $doc->ownerDocument->saveXML($doc);

            return $this->getRemoveXmlDeclaration() ? preg_replace('#^<\?xml[^?]*?\?>\s*#', '', $content) :$content;
        }

        if ($this->getMethod() === static::METHOD_TEXT) {
            return $doc->textContent;
        }

        // Add missing HTML default tags
        $content = $this->getDoctype() . "\n";

        $xPathFactory = new Factory();
        $headerXPath = $xPathFactory->create('/html/head');

        $header = $headerXPath->query($doc);

        if ($header->count()) {
            $meta = $this->getMetaCharsetTag($doc);

            if ($header->item(0)->hasChildNodes()) {
                $header->item(0)->insertBefore($meta, $header->item(0)->childNodes->item(0));
            } else {
                $header->item(0)->appendChild($meta);
            }
        }


        return $content . ($doc instanceof \DOMDocument ? $doc->saveHTML() : $doc->ownerDocument->saveHTML($doc));
    }
}
