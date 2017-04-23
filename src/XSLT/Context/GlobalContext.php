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

use Jdomenechb\XSLT2Processor\XSLT\Template\Key;
use Jdomenechb\XSLT2Processor\XSLT\Template\TemplateList;

/**
 * Defines the base context shared between templates.
 *
 * @author jdomenechb
 */
class GlobalContext
{
    const NAMESPACE_DEFAULT = 'default';
    const NAMESPACE_XSL = 'xsl';

    /**
     * Namespaces available throughout the XSL documents.
     *
     * @var \ArrayObject
     */
    protected $namespaces;

    /**
     * @var Key[]
     */
    protected $keys;

    /**
     * @var \ArrayObject
     */
    protected $extensionElementPrefixes;

    /**
     * @return TemplateList
     */
    protected $templates;

    /**
     * @var \SplStack
     */
    protected $stylesheetStack;

    /**
     * @var \ArrayObject
     */
    protected $attributeSets;

    public function __construct()
    {
        $this->namespaces = new \ArrayObject([
            'default' => null,
            'fn' => 'http://www.w3.org/2005/xpath-functions',
        ]);

        $this->keys = new \ArrayObject();
        $this->templates = new TemplateList();
        $this->stylesheetStack = new \SplStack();
        $this->extensionElementPrefixes = new \ArrayObject(['fn']);
        $this->attributeSets = new \ArrayObject();
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

    /**
     * @todo Support other namespaces when the prefix "default" is used
     *
     * @return string
     */
    public function getDefaultNamespace()
    {
        return static::NAMESPACE_DEFAULT;
    }

    /**
     * @return Key[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param Key[]
     * @param mixed $keys
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
    }

    /**
     * @return TemplateList
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param TemplateList $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

    /**
     * @return \ArrayObject
     */
    public function getExtensionElementPrefixes()
    {
        return $this->extensionElementPrefixes;
    }

    /**
     * @param \ArrayObject $extensionElementPrefixes
     */
    public function setExtensionElementPrefixes($extensionElementPrefixes)
    {
        $this->extensionElementPrefixes = $extensionElementPrefixes;
    }

    /**
     * @return \SplStack
     */
    public function getStylesheetStack()
    {
        return $this->stylesheetStack;
    }

    /**
     * @param \SplStack $stylesheetStack
     */
    public function setStylesheetStack($stylesheetStack)
    {
        $this->stylesheetStack = $stylesheetStack;
    }

    /**
     * @return \ArrayObject
     */
    public function getAttributeSets()
    {
        return $this->attributeSets;
    }

    /**
     * @param \ArrayObject $attributeSets
     */
    public function setAttributeSets($attributeSets)
    {
        $this->attributeSets = $attributeSets;
    }
}
