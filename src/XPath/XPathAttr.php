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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

/**
 * Class that represents an attribute in an xPath expression.
 *
 * @author jdomenechb
 */
class XPathAttr extends AbstractXPath
{
    /**
     * Name of the attribute.
     *
     * @var string
     */
    protected $name;

    /**
     * Namespace of the attribute.
     *
     * @var string
     */
    protected $namespace;

    /**
     * {@inheritdoc}
     */
    public function evaluate($context)
    {
        if (!$context instanceof DOMNodeList) {
            $context = new DOMNodeList($context);
        }

        $results = new DOMNodeList();
        $namespace = $this->getNamespace()?
            $this->getGlobalContext()->getNamespaces()[$this->getNamespace()]
            : $this->getGlobalContext()->getNamespaces()[$this->getGlobalContext()->getDefaultNamespace()];

        foreach ($context as $contextNode) {
            foreach ($contextNode->attributes as $attribute) {
                /** @var $attribute \DOMAttr */
                if (
                    (
                        $attribute->localName == $this->getName()
                        && $attribute->namespaceURI == $namespace
                    ) || $this->getName() == '*'
                ) {
                    $results->merge(new DOMNodeList($attribute));
                }
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $xPath
     */
    public function parse($xPath)
    {
        if (!preg_match('#^@[a-z-:]+$#', $xPath)) {
            return false;
        }

        $parts = explode(':', substr($xPath, 1));

        if (count($parts) > 1) {
            $this->setNamespace($parts[0]);
            $this->setName($parts[1]);
        } else {
            $this->setName($parts[0]);
        }

        return true;
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function toString()
    {
        return '@' . ($this->getNamespace()? $this->getNamespace() . ':': '') . $this->getName();
    }

    /**
     * Returns the name of the attribute.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the attributes.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

}
