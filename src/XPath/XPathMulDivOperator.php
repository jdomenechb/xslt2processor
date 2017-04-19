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

use Jdomenechb\XSLT2Processor\XML\NotANumber;

class XPathMulDivOperator extends AbstractXPathOperator
{
    protected static $operators;

    public static function getOperators()
    {
        if (!static::$operators) {
            static::$operators = [
                '*' => function ($left, $right) {
                    if (!is_numeric($left) || !is_numeric($right)) {
                        return new NotANumber();
                    }

                    return $left * $right;
                },
                'div' => function ($left, $right) {
                    if (!is_numeric($left) || !is_numeric($right)) {
                        return new NotANumber();
                    }

                    return $left / $right;
                },
                'mod' => function ($left, $right) {
                    if (!is_numeric($left) || !is_numeric($right)) {
                        return new NotANumber();
                    }

                    return $left % $right;
                },
            ];
        }

        return static::$operators;
    }
}
