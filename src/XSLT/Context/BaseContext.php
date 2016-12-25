<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Context;

/**
 * Defines the base context shared between templates.
 * @author jdomenechb
 */
class BaseContext
{
    const NAMESPACE_DEFAULT = 'default';
    const NAMESPACE_XSL = 'xsl';

    /**
     * Namespaces available throughout the XSL documents.
     * @var \ArrayObject
     */
    protected $namespaces;

    public function __construct()
    {
        $this->namespaces = new \ArrayObject(['default' => null]);
    }

    /**
     * @return \ArrayObject
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * @param \ArrayObject $namespaces
     */
    public function setNamespaces($namespaces)
    {
        $this->namespaces = $namespaces;
    }


}