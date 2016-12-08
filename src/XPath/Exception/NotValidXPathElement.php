<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\Exception;

use RuntimeException;

/**
 * Exception throw when trying to parse an xPath element that does not match the rules of the element in the
 * constructor.
 * @author jdomenechb
 */
class NotValidXPathElement extends RuntimeException
{
    /**
     * Cosntructor.
     * @param string $xPath The xPath to be parsed
     * @param string $className The name of the class who tried to parse the xPath
     */
    public function __construct($xPath, $className)
    {
        parent::__construct($xPath . ' is not a valid ' . $className);
    }
}
