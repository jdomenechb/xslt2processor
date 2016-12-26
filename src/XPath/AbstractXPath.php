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
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

/**
 * Abstract class for implementing an XPath element.
 *
 * @author jdomenechb
 */
abstract class AbstractXPath implements ExpressionInterface
{
    protected $namespaces;
    protected $globalContext;
    protected $templateContext;

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
        throw new \RuntimeException('Not implemented yet in ' . get_called_class());
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }


    public function setGlobalContext(GlobalContext $context)
    {
        $this->globalContext = $context;

        throw new \RuntimeException('Not implemented yet in ' . get_called_class());
    }

    public function setTemplateContext(TemplateContext $context)
    {
        $this->templateContext = $context;

        throw new \RuntimeException('Not implemented yet in ' . get_called_class());
    }

    /**
     * @return GlobalContext
     */
    public function getGlobalContext()
    {
        return $this->globalContext;
    }

    /**
     * @return TemplateContext
     */
    public function getTemplateContext()
    {
        return $this->templateContext;
    }


}
