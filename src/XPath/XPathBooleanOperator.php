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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

class XPathBooleanOperator extends AbstractXPathOperator
{
    protected static $operators;

    /**
     * @return array
     */
    public static function getOperators()
    {
        if (!static::$operators) {
            static::$operators = [
                '!=' => function ($left, $right) {
                    if (!$left instanceof DOMNodeList && !$right instanceof DOMNodeList) {
                        return $left != $right;
                    }

                    // Logic for one side object
                    if ($left instanceof DOMNodeList) {
                        $obj = $left;
                        $other = $right;
                    } else {
                        $obj = $right;
                        $other = $left;
                    }

                    foreach ($obj as $objNode) {
                        if ($other instanceof DOMNodeList) {
                            foreach ($other as $otherNode) {
                                if ($objNode->nodeValue != $otherNode->nodeValue) {
                                    return true;
                                }
                            }
                        } elseif (!is_object($other) && $objNode->nodeValue != $other) {
                            return true;
                        } elseif (is_object($other)) {
                            throw new \RuntimeException('Unidentified object');
                        }
                    }

                    return false;
                },
                '=' => function ($left, $right) {
                    if (!$left instanceof DOMNodeList && !$right instanceof DOMNodeList) {
                        return $left == $right;
                    }

                    // Logic for one side object
                    if ($left instanceof DOMNodeList) {
                        $obj = $left;
                        $other = $right;
                    } else {
                        $obj = $right;
                        $other = $left;
                    }

                    foreach ($obj as $objNode) {
                        if ($other instanceof DOMNodeList) {
                            foreach ($other as $otherNode) {
                                if ($objNode->nodeValue == $otherNode->nodeValue) {
                                    return true;
                                }
                            }
                        } elseif (!is_object($other) && $objNode->nodeValue == $other) {
                            return true;
                        } elseif (is_object($other)) {
                            throw new \RuntimeException('Unidentified object');
                        }
                    }

                    return false;
                },
            ];
        }

        return static::$operators;
    }
}
