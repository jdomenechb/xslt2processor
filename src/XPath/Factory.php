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

class Factory
{
    public function create($expression)
    {
        $expression = trim($expression);

        // Parse string
        if ($tmp = XPathString::parseXPath($expression)) {
            return $tmp;
        }

        // Parse number
        if ($tmp = XPathNumber::parseXPath($expression)) {
            return $tmp;
        }

        // Parse var
        if ($tmp = XPathVariable::parseXPath($expression)) {
            return $tmp;
        }

        // Analyze parentheses
        if (substr($expression, -1) === ')') {
            $expressionParserHelper = new ExpressionParserHelper();
            $history = $expressionParserHelper->subExpressionLevelAnalysis($expression, '(', ')');

            if (
                // Has more than one level
                $history !== 0
                // It should not return to the level 0 in any point inside
                && strpos(substr($history, 1, -1), '0') === false
            ) {
                // Is a function?
                if ($tmp = XPathFunction::parseXPath($expression)) {
                    return $tmp;
                }

                // Is a subexpression?
                if ($tmp = XPathSub::parseXPath($expression)) {
                    return $tmp;
                }
            }
        }

        $expressionToLower = strtolower($expression);

        // Parse OR
        // Fast search first
        if (strpos($expressionToLower, ' or ') !== false) {
            $pieces = $this->parseByOperator(' or ', $expression);

            if (count($pieces) > 1) {
                return new XPathOr($pieces);
            }
        }

        // Parse AND
        // Fast search first
        if (strpos($expressionToLower, ' and ') !== false) {
            $pieces = $this->parseByOperator(' and ', $expression);

            if (count($pieces) > 1) {
                return new XPathAnd($pieces);
            }
        }

        // Parse boolean operator
        if ($tmp = XPathBooleanOperator::parseXPath($expression)) {
            return $tmp;
        }

        // Parse comparison operator
        if ($tmp = XPathCompareOperator::parseXPath($expression)) {
            return $tmp;
        }

        // Parse add minus
        if ($tmp = XPathSumSubOperator::parseXPath($expression)) {
            return $tmp;
        }

        // Parse mul div
        if ($tmp = XPathMulDivOperator::parseXPath($expression)) {
            return $tmp;
        }

        // Parse union
        if ($tmp = XPathUnionOperator::parseXPath($expression)) {
            return $tmp;
        }

        // Parse selector
        if ($tmp = XPathSelector::parseXPath($expression)) {
            return $tmp;
        }

        // Parse every level path
        if ($tmp = XPathEveryLevelPath::parseXPath($expression)) {
            return $tmp;
        }

        // Parse path
        if ($tmp = XPathPath::parseXPath($expression)) {
            return $tmp;
        }

        // Parse attribute node
        if ($tmp = XPathAttr::parseXPath($expression)) {
            return $tmp;
        }

        // Parse axis
        if ($tmp = XPathAxis::parseXPath($expression)) {
            return $tmp;
        }

        // Parse normal node
        return XPathPathNode::parseXPath($expression);
    }

    public function parseByOperator($operator, $string)
    {
        $level = 0;
        $sBLevel = 0;
        $matches = [];

        $offset = 0;
        $offsetPiece = 0;
        $stringToLower = strtolower($string);
        $stringLength = strlen($string);
        $lengthOperator = strlen($operator);
        $operator = strtolower($operator);

        while ($offset < $stringLength) {
            // Position of the left parenthesis
            $lParPos = strpos($stringToLower, '(', $offset);

            if ($lParPos === false) {
                $lParPos = $stringLength;
            }

            // Position of the right parenthesis
            $rParPos = strpos($stringToLower, ')', $offset);

            if ($rParPos === false) {
                $rParPos = $stringLength;
            }

            // Position of the left square bracket
            $lSBPos = strpos($stringToLower, '[', $offset);

            if ($lSBPos === false) {
                $lSBPos = $stringLength;
            }

            // Position of the right square bracket
            $rSBPos = strpos($stringToLower, ']', $offset);

            if ($rSBPos === false) {
                $rSBPos = $stringLength;
            }

            // Position of the operator
            if ($level === 0) {
                $opPos = strpos($stringToLower, $operator, $offset);

                if ($opPos === false) {
                    $opPos = $stringLength;
                }
            } else {
                $opPos = $stringLength;
            }

            // Calculate min
            $min = min($lParPos, $rParPos, $lSBPos, $rSBPos, $opPos);

            if ($min === $stringLength) {
                $offset = $stringLength;
            } elseif ($min == $lParPos) {
                if ($sBLevel === 0) {
                    ++$level;
                }

                $offset = $min + 1;
            } elseif ($min == $rParPos) {
                if ($sBLevel === 0) {
                    --$level;
                }

                $offset = $min + 1;
            } elseif ($min == $lSBPos) {
                if ($level === 0) {
                    ++$sBLevel;
                }

                $offset = $min + 1;
            } elseif ($min == $rSBPos) {
                if ($level === 0) {
                    --$sBLevel;
                }

                $offset = $min + 1;
            } elseif ($min === $opPos) {
                if ($level == 0 && $sBLevel === 0) {
                    $matches[] = trim(substr($string, $offsetPiece, $opPos - $offsetPiece));
                    $offsetPiece = $min + $lengthOperator;
                }

                $offset = $min + $lengthOperator;
            }
        }

        $matches[] = trim(substr($string, $offsetPiece, $stringLength - $offsetPiece));

        return $matches;
    }

    public function createFromAttributeValue($attributeValue)
    {
        // Move inside function
        $expressionParserHelper = new ExpressionParserHelper();
        $levels = $expressionParserHelper->parseFirstLevelSubExpressions($attributeValue, '{', '}');

        $xPath = XPathAttributeValueTemplate::parseXPath($levels);

        return $xPath;
    }
}
