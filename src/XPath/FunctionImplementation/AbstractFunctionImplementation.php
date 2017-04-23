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
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XPath\Expression\Converter;

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
        return Converter::fromDOMToString($value);
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
        // TODO: Migrate to converter
        if ($value instanceof DOMNodeList) {
            return $value->length !== 0;
        }

        if ($value instanceof DOMResultTree) {
            return (bool) $value->getBaseNode();
        }

        return (bool) $value;
    }

    /**
     * Converts the given value into a int.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function valueAsInt($value)
    {
        // TODO: Migrate to converter
        return (int) $this->valueAsString($value);
    }
}
