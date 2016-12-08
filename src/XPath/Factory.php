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
        if (
            substr($expression, 0, 1) === "'"
            && substr($expression, -1) === "'"
            && strpos(substr(static::analyzeLevels($expression, "'", "'"), 1, -1), '0') === false
        ) {
            return new XPathString($expression);
        }

        // Parse number
        $tmp = new XPathNumber();

        if ($tmp->parse($expression)) {
            return $tmp;
        }

        // Parse var
        if (preg_match('#^\$[a-z0-9_]+$#i', $expression)) {
            return new XPathVariable($expression);
        }

        // Analyze parentheses
        if (substr($expression, -1) === ')') {
            $history = static::analyzeLevels($expression, '(', ')');

            // Is a function?
            if (
                // Has more than one level
                strlen($history) > 1
                // It should not return to the level 0 in any point inside
                && strpos(substr($history, 1, -1), '0') === false
                // It should match a function
                && preg_match('#^[a-z-]+\(.*\)$#', $expression)
            ) {
                return new XPathFunction($expression);
            }

            // Is a subexpression?
            if (
                // Has more than one level
                strlen($history) > 1
                // It should not return to the level 0 in any point inside
                && strpos(substr($history, 1, -1), '0') === false
                // It should match a sub
                && preg_match('#^\(.*\)$#', $expression)
            ) {
                return new XPathSub($expression);
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

        // Parse comparison operator
        $tmp = new XPathCompareOperator();

        if ($tmp->parse($expression)) {
            return $tmp;
        }

        // Parse add minus
        $tmp = new XPathSumSubOperator();

        if ($tmp->parse($expression)) {
            return $tmp;
        }

        // Parse mul div
        $tmp = new XPathMulDivOperator();

        if ($tmp->parse($expression)) {
            return $tmp;
        }

        // Parse union
        $tmp = new XPathUnionOperator();

        if ($tmp->parse($expression)) {
            return $tmp;
        }

        // Parse path
        if (preg_match('#/#i', $expression)) {
            return new XPathPath($expression);
        }

        return new XPathPathNode($expression);
    }

    public function analyzeLevels($expression, $start, $end)
    {
        $history = '0';
        $level = 0;
        $offset = 0;

        $expressionLength = strlen($expression);

        while ($offset < $expressionLength) {
            $sPos = strpos($expression, $start, $offset);

            if ($sPos === false) {
                $sPos = $expressionLength;
            }

            $ePos = strpos($expression, $end, $offset);

            if ($ePos === false) {
                $ePos = $expressionLength;
            }

            $min = min($sPos, $ePos);

            if ($min === $expressionLength) {
                break;
            } elseif ($min === $sPos) {
                ++$level;
            } elseif ($min === $ePos) {
                --$level;
            }

            $history .= $level;
            $offset = $min + 1;
        }

        return $history;
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
        $expressionParserHelper = new ExpressionParserHelper();
        $levels = $expressionParserHelper->parseFirstLevelSubExpressions($attributeValue, '{', '}');

        $xPath = new XPathAttributeValueTemplate($levels);

        return $xPath;
    }
}
