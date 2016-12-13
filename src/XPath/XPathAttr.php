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

use DOMXPath;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

/**
 * Class that represents an attribute in an xPath expression
 *
 * @author jdomenechb
 */
class XPathAttr extends AbstractXPath
{
    /**
     * Name of the attribute
     * @var type
     */
    protected $name;

    /**
     * {@inheritDoc}
     */
    public function evaluate($context, DOMXPath $xPathReference)
    {
        foreach ($context->attributes as $attribute) {
            if ($attribute->nodeName == $this->getName()) {
                return new DOMNodeList($attribute);
            }
        }

        return new DOMNodeList();
    }

    /**
     * {@inheritDoc}
     * @param string $xPath
     */
    public function parse($xPath)
    {

        if (!preg_match('#^@[a-z-]+$#', $xPath)) {
            return false;
        }

        $this->setName(substr($xPath, 1));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultNamespacePrefix($prefix)
    {
        // This method is intended to be left empty
    }

    /**
     * {@inheritDoc}
     */
    public function setVariableValues(array $values)
    {
        // This method is intended to be left empty
    }

    /**
     * {@inheritDoc}
     * @return string
     */
    public function toString()
    {
        return '@' . $this->getName();
    }

    /**
     * Returns the name of the attribute
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the attribute.s
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
