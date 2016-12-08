<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 11:03
 */

namespace Jdomenechb\XSLT2Processor\XPath;

class XPathSumSubOperator extends AbstractXPathOperator
{
    protected static $operators;

    public static function getOperators()
    {
        if (!static::$operators) {
            static::$operators = [
                '+' => function ($left, $right) { return $left + $right; },
                '-' => function ($left, $right) { return $left - $right; },
            ];
        };

        return static::$operators;
    }
}