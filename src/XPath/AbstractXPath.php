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

use Jdomenechb\XSLT2Processor\XPath\Exception\NotValidXPathElement;

/**
 * Abstract class for implementing an XPath element.
 *
 * @author jdomenechb
 */
abstract class AbstractXPath implements ExpressionInterface
{
    protected $namespaces;

    /**
     * Constructor.
     *
     * @param mixed $xPath
     */
    public function __construct($xPath = null)
    {
        // If the xPath has been given, parse the xPath
        if (!is_null($xPath) && !$this->parse($xPath)) {
            throw new NotValidXPathElement($xPath, get_called_class());
        }
    }

    /**
     * Returns the xPath representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function query($context)
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * {@inheritDoc}
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }
}
