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

class XPathMulDivOperator extends AbstractXPathOperator
{
    protected static $operators;

    public static function getOperators()
    {
        if (!static::$operators) {
            static::$operators = [
                '*' => function ($left, $right) {
                    return $left * $right;
                },
                'div' => function ($left, $right) {
                    return $left / $right;
                },
                'mod' => function ($left, $right) {
                    return $left % $right;
                },
            ];
        }

        return static::$operators;
    }
}
