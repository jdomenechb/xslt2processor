<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\Expression;

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;

class Converter
{
    /**
     * Given a DOM object, it will provide its string value given the internal XSLT rules.
     *
     * @param $value
     *
     * @return string
     */
    public static function fromDOMToString($value)
    {
        if ($value instanceof DOMResultTree) {
            $value = $value->evaluate();
        }

        if ($value instanceof \DOMNodeList || $value instanceof DOMNodeList) {
            if ($value->length === 0) {
                return '';
            }

            return $value->item(0)->nodeValue;
        }

        if ($value instanceof \DOMText) {
            return $value->nodeValue;
        }

        return (string) $value;
    }

    /**
     * Given a DOM object, it will provide its string value given the internal XSLT rules.
     *
     * @param $value
     *
     * @return string
     */
    public static function fromDOMToInteger($value)
    {
        return (int) static::fromDOMToString($value);
    }

    /**
     * Given a DOM object, it will provide its float value given the internal XSLT rules.
     *
     * @param $value
     *
     * @return string
     */
    public static function fromDOMToFloat($value)
    {
        return (float) static::fromDOMToString($value);
    }

    /**
     * Given a DOM object, it will provide the DOMDocuement of the first of the elements it represents.
     *
     * @param $value
     *
     * @return string
     */
    public static function fromDOMToDOMDocument($value)
    {
        if ($value instanceof \DOMDocument) {
            return $value;
        }

        if ($value instanceof DOMNodeList) {
            $value = $value->item(0);
        }

        if ($value instanceof \DOMNode && !$value instanceof \DOMDocument) {
            $value = $value->ownerDocument;
        }

        return $value;
    }
}
