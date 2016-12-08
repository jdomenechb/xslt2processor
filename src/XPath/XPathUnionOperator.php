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
