<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 11:03
 */

namespace Jdomenechb\XSLT2Processor\XPath;

class XPathUnionOperator extends AbstractXPathOperator
{
    protected static $operators;

    public static function getOperators()
    {
        if (!static::$operators) {
            $callback = function ($left, $right) {
                $result = [];

                foreach ($left as $node) {
                    $result[] = $node;
                }

                foreach ($right as $node) {
                    $result[] = $node;
                }

                return $result;
            };

            static::$operators = [
                '|' => $callback,
                'union' => $callback,
            ];
        }

        return static::$operators;
    }
}