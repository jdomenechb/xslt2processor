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

use Jdomenechb\XSLT2Processor\XPath\Expression\ExpressionParserHelper;

/**
 * Factory class used as entry point to create XPath classes.
 * @package Jdomenechb\XSLT2Processor\XPath
 * @author jdomemechb
 */
class Factory
{
    /**
     * Memory-based cache for reusing XPaths already parsed.
     * @var array
     */
    protected static $xPathCache = [];

    /**
     * Creates an XPath class system from the given XPath.
     *
     * @param $expression
     * @return ExpressionInterface
     */
    public function create($expression)
    {
        $expression = trim($expression);

        if (isset(static::$xPathCache[$expression])) {
            // Cached: return new instances from object
            return unserialize(static::$xPathCache[$expression]);
        }

        // Parse string
        if ($tmp = XPathString::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse number
        if ($tmp = XPathNumber::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse var
        if ($tmp = XPathVariable::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse for
        if ($tmp = XPathFor::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        $expressionParserHelper = new ExpressionParserHelper();

        // Analyze parentheses
        if (substr($expression, -1) === ')') {
            $history = $expressionParserHelper->subExpressionLevelAnalysis($expression, '(', ')');

            if (
                // Has more than one level
                $history !== 0
                // It should not return to the level 0 in any point inside
                && strpos(substr($history, 1, -1), '0') === false
            ) {
                // Is a function?
                if ($tmp = XPathFunction::parseXPath($expression)) {
                    static::$xPathCache[$expression] = serialize($tmp);
                    return $tmp;
                }

                // Is a subexpression?
                if ($tmp = XPathSub::parseXPath($expression)) {
                    static::$xPathCache[$expression] = serialize($tmp);
                    return $tmp;
                }
            }
        }

        $expressionToLower = strtolower($expression);

        // Parse OR
        // TODO: Move inside function
        // Fast search first
        if (strpos($expressionToLower, ' or ') !== false) {
            $pieces = $expressionParserHelper->explodeRootLevel(' or ', $expression);

            if (count($pieces) > 1) {
                return new XPathOr($pieces);
            }
        }

        // Parse AND
        // TODO: Move inside function
        // Fast search first
        if (strpos($expressionToLower, ' and ') !== false) {
            $pieces = $expressionParserHelper->explodeRootLevel(' and ', $expression);

            if (count($pieces) > 1) {
                return new XPathAnd($pieces);
            }
        }

        // Parse boolean operator
        if ($tmp = XPathBooleanOperator::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse comparison operator
        if ($tmp = XPathCompareOperator::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse add minus
        if ($tmp = XPathSumSubOperator::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse mul div
        if ($tmp = XPathMulDivOperator::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse union
        if ($tmp = XPathUnionOperator::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse selector
        if ($tmp = XPathSelector::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse every level path
        if ($tmp = XPathEveryLevelPath::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse path
        if ($tmp = XPathPath::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse attribute node
        if ($tmp = XPathAttr::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse axis
        if ($tmp = XPathAxis::parseXPath($expression)) {
            static::$xPathCache[$expression] = serialize($tmp);
            return $tmp;
        }

        // Parse normal node
        $tmp = XPathPathNode::parseXPath($expression);
        static::$xPathCache[$expression] = serialize($tmp);
        return $tmp;
    }


    public function createFromAttributeValue($attributeValue)
    {
        $expressionParserHelper = new ExpressionParserHelper();
        $levels = $expressionParserHelper->parseFirstLevelSubExpressions($attributeValue, '{', '}');

        return XPathAttributeValueTemplate::parseXPath($levels);
    }

    public static function cleanXPathCache()
    {
        static::$xPathCache = [];
    }
}
