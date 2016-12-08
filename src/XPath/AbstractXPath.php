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

/**
 * Abstract class for implementing an XPath element.
 *
 * @author jdomenechb
 */
abstract class AbstractXPath implements ExpressionInterface
{
    /**
     * Constructor.
     *
     * @param mixed $xPath
     */
    public function __construct($xPath)
    {
        if (!$this->parse($xPath)) {
            throw new NotValidXPathElement($xPath, get_called_class());
        }
    }

    /**
     * Returns the xPath representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return (string) $this->getNumber();
    }
}
