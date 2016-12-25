<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\FunctionImplementation;

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

abstract class AbstractFunctionImplementation implements FunctionImplementationInterface
{
    /**
     * Converts the given value into an string.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function valueAsString($value)
    {
        if ($value instanceof \DOMNodeList || $value instanceof DOMNodeList) {
            if ($value->length == 0) {
                return '';
            }

            return $value->item(0)->nodeValue;
        }

        return (string) $value;
    }

    /**
     * Converts the given value into a boolean.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function valueAsBool($value)
    {
        if ($value instanceof DOMNodeList) {
            return $value->length !== 0;
        }

        return (bool) $value;
    }
}
