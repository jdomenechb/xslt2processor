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

use DOMNode;
use DOMXPath;
use Jdomenechb\XSLT2Processor\XPath\Exception\NotXPathString;

/**
 * Represents an xPath string.
 * @author jdomenechb
 */
class XPathString extends AbstractXPath
{
    /**
     *
     * @var string
     */
    protected $string;

    /**
     * {@inheritdoc}
     *
     * The given xPath must be enclosed in simple quotes to be considered an string.
     */
    public function parse($xPath)
    {
        if (substr($xPath, 0, 1) !== "'" || substr($xPath, -1) !== "'") {
            throw new NotXPathString;
        }

        $this->setString(substr($xPath, 1, -1));
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return "'" . $this->getString() . "'";
    }

    public function setDefaultNamespacePrefix($prefix)
    {
    }

    /**
     * @return mixed
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param mixed $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    public function setVariableValues(array $values)
    {
    }

    public function evaluate(DOMNode $context, DOMXPath $xPathReference)
    {
        return $this->getString();
    }

}
