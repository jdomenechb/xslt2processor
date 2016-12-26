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
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;

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
     * {@inheritdoc}
     */
    public function evaluate($context)
    {
        if (!$context instanceof DOMNodeList) {
            $context = new DOMNodeList($context);
        }

        $results = new DOMNodeList();

        foreach ($context as $contextNode) {
            foreach ($contextNode->attributes as $attribute) {
                if ($attribute->nodeName == $this->getName()) {
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
        if (!preg_match('#^@[a-z-]+$#', $xPath)) {
            return false;
        }

        $this->setName(substr($xPath, 1));

        return true;
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function toString()
    {
        return '@' . $this->getName();
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

    public function setGlobalContext(GlobalContext $context)
    {
        try {
            parent::setGlobalContext($context);
        } catch (\RuntimeException $e) {}
    }

    public function setTemplateContext(TemplateContext $context)
    {
        try {
            parent::setTemplateContext($context);
        } catch (\RuntimeException $e) {}
    }
}
