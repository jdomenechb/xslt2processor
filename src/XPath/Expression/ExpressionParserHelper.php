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

use RuntimeException;

/**
 * Helper class with methods to analyze and parse expressions.
 *
 * @author jdomenechb
 */
class ExpressionParserHelper
{
    /**
     * Given an expression, a start delimiter and an end delimiter, returns a list of pieces from the expression given
     * in which the odd indexes are the expression parts contained in-between this symbols (but only the first level),
     * and the even and zero indexes the parts outside the delimiter symbols.
     *
     * @param string $expression
     * @param string $start
     * @param string $end
     *
     * @return string[] values with odd indexes are parts outside the subexpression, values with even indexes the
     *                  subexpressions
     */
    public function parseFirstLevelSubExpressions($expression, $start, $end)
    {
        if ($start === '(') {
            $avoidL = '[';
            $avoidR = ']';
        } elseif ($start === '[') {
            $avoidL = '[';
            $avoidR = ']';
        } elseif ($start === '{') {
            $avoidL = '{';
            $avoidR = '}';
        } else {
            throw new \RuntimeException('Delimiters not recognised');
        }

        $level = 0;
        $avoidLevel = 0;
        $matches = [];

        $offset = 0;
        $offsetPiece = 0;
        $stringToLower = strtolower($expression);
        $stringLength = strlen($expression);
        $lengthSOperator = strlen($start);
        $lengthEOperator = strlen($end);
        $lengthALOperator = strlen($avoidL);
        $lengthAROperator = strlen($avoidR);

        while ($offset < $stringLength) {
            // Position of the start
            $lParPos = strpos($stringToLower, $start, $offset);

            if ($lParPos === false) {
                $lParPos = $stringLength;
            }

            // Position of the right parenthesis
            $rParPos = strpos($stringToLower, $end, $offset);

            if ($rParPos === false) {
                $rParPos = $stringLength;
            }

            // Position of the start
            $lAPos = strpos($stringToLower, $avoidL, $offset);

            if ($lAPos === false) {
                $lAPos = $stringLength;
            }

            // Position of the end
            $rAPos = strpos($stringToLower, $avoidR, $offset);

            if ($rAPos === false) {
                $rAPos = $stringLength;
            }

            // Calculate min
            $min = min($lParPos, $rParPos, $lAPos, $rAPos);

            if ($min === $stringLength) {
                $offset = $stringLength;
            } elseif ($min === $lParPos) {
                if (!$avoidLevel) {
                    ++$level;

                    if ($level === 1) {
                        $matches[] = trim(substr($expression, $offsetPiece, $lParPos - $offsetPiece));
                        $offsetPiece = $min + $lengthSOperator;
                    }
                }

                $offset = $min + $lengthSOperator;
            } elseif ($min === $rParPos) {
                if (!$avoidLevel) {
                    --$level;

                    if ($level === 0) {
                        $matches[] = trim(substr($expression, $offsetPiece, $rParPos - $offsetPiece));
                        $offsetPiece = $min + $lengthEOperator;
                    }
                }

                $offset = $min + $lengthEOperator;
            } elseif ($min === $lAPos) {
                if (!$level) {
                    ++$avoidLevel;
                }

                $offset = $min + $lengthALOperator;
            } elseif ($min === $rAPos) {
                if (!$level) {
                    --$avoidLevel;
                }

                $offset = $min + $lengthAROperator;
            }
        }

        $matches[] = trim(substr($expression, $offsetPiece, $stringLength - $offsetPiece));

        return $matches;
    }

    /**
     * Given an expression, a start delimiter and an end delimiter, the method returns an string with all the level
     * numbers of the subexpression delimited by the start and end string.
     *
     * @param string $expression
     * @param string $start
     * @param string $end
     *
     * @return string The level string. For example, if a subexpression is not available in the main expression, the
     *                method will return '0'. In the case of an expression like
     *                'expr1 or (expr2 and (expression1 and (expression2)) or (expresssion))', being the parenthesis the delimiters,
     *                the returned string would be '012321210'.
     */
    public function subExpressionLevelAnalysis($expression, $start, $end)
    {
        if ($start === '(') {
            $avoidL = '[';
            $avoidR = ']';
        } elseif ($start === '[') {
            $avoidL = '[';
            $avoidR = ']';
        } else {
            throw new \RuntimeException('Delimiters not recognised');
        }

        $history = '0';
        $level = 0;
        $levelAvoid = 0;
        $offset = 0;

        $expressionLength = strlen($expression);

        while ($offset < $expressionLength) {
            // Detect positions of the delimiters
            $sPos = strpos($expression, $start, $offset);

            if ($sPos === false) {
                $sPos = $expressionLength;
            }

            $ePos = strpos($expression, $end, $offset);

            if ($ePos === false) {
                $ePos = $expressionLength;
            }

            // Detect positions of delimiters to avoid
            $aLPos = strpos($expression, $avoidL, $offset);

            if ($aLPos === false) {
                $aLPos = $expressionLength;
            }

            $aRPos = strpos($expression, $avoidR, $offset);

            if ($aRPos === false) {
                $aRPos = $expressionLength;
            }

            // Decide depending which comes next
            $min = min($sPos, $ePos, $aLPos, $aRPos);

            if ($min === $expressionLength) {
                break;
            } elseif ($min === $sPos && !$levelAvoid) {
                ++$level;

                if ($level > 9) {
                    throw new RuntimeException('Only expressions up to 10 levels are supported');
                }

                $history .= $level;
            } elseif ($min === $ePos && !$levelAvoid) {
                --$level;
                $history .= $level;
            } elseif ($min === $aLPos) {
                if (!$level) {
                    ++$levelAvoid;
                }
            } elseif ($min === $aRPos) {
                if (!$level) {
                    ++$levelAvoid;
                }
            }

            $offset = $min + 1;
        }

        return $history;
    }

    /**
     * Given an expresison, a delimiter of the literal and the escaped literal, the method returns an string with all
     * the level of the literals delimited by the given symbol.
     *
     * @param string $expression
     * @param string $delimiter
     * @param string $escaped
     * @param mixed  $escapedLiteral
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function literalLevelAnalysis($expression, $delimiter, $escapedLiteral)
    {
        $history = '0';
        $level = 0;
        $offset = 0;

        $expressionLength = mb_strlen($expression);

        while ($offset < $expressionLength) {
            // Detect positions of the delimiter
            $dPos = mb_strpos($expression, $delimiter, $offset);

            if ($dPos === false) {
                $dPos = $expressionLength;
            }

            // Detect position of the escaped symbol
            $ePos = mb_strpos($expression, $escapedLiteral, $offset);

            if ($ePos === false) {
                $ePos = $expressionLength;
            }

            // Decide depending which comes next
            $min = min($dPos, $ePos);

            if ($min === $expressionLength) {
                break;
            } elseif ($min === $dPos && $dPos !== $ePos) {
                if ($level > 0) {
                    --$level;
                } else {
                    ++$level;
                }

                $history .= $level;
                $offset = $min + 1;
            } else {
                $offset = $min + mb_strlen($escapedLiteral);
            }
        }

        return $history;
    }

    /**
     * Given an expression and a glue string, it explodes the string separating it by the given glue. However, it only
     * splits it at the first level, meaning it ignores strings, parenthesis and square brackets that could be in the
     * string.
     * @param $glue
     * @param $expression
     * @return array
     */
    public function explodeRootLevel($glue, $expression)
    {
        $level = 0;
        $sqLevel = 0;
        $isString = false;
        $matches = [];

        $offset = 0;
        $offsetPiece = 0;

        $stringToLower = strtolower($expression);
        $stringLength = strlen($expression);

        while ($offset < $stringLength) {
            $min = $stringLength;

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

            // Position of the left sq bracket
            $lSqPos = strpos($stringToLower, '[', $offset);

            if ($lSqPos === false) {
                $lSqPos = $stringLength;
            }

            // Position of the right sq bracket
            $rSqPos = strpos($stringToLower, ']', $offset);

            if ($rSqPos === false) {
                $rSqPos = $stringLength;
            }

            // Position of the string
            $strPos = strpos($stringToLower, "'", $offset);

            if ($strPos === false) {
                $strPos = $stringLength;
            }

            // Position of the glue
            $gluePos = strpos($stringToLower, $glue, $offset);

            if ($gluePos === false) {
                $gluePos = $stringLength;
            }

            // Calculate min
            $min = min($lParPos, $rParPos, $lSqPos, $rSqPos, $strPos, $gluePos);

            if ($min === $stringLength) {
                $offset = $stringLength;
            } elseif ($min === $lParPos) {
                if ($sqLevel === 0) {
                    ++$level;
                }

                $offset = $min + 1;
            } elseif ($min === $rParPos) {
                if ($sqLevel === 0) {
                    --$level;
                }

                $offset = $min + 1;
            } elseif ($min === $lSqPos) {
                if ($level === 0) {
                    ++$sqLevel;
                }

                $offset = $min + 1;
            } elseif ($min === $rSqPos) {
                if ($level === 0) {
                    --$sqLevel;
                }

                $offset = $min + 1;
            } elseif ($min === $strPos) {
                if ($level === 0 && $sqLevel == 0) {
                    $isString = !$isString;
                }

                $offset = $min + 1;
            } elseif ($min == $gluePos) {
                $offset = $min + strlen($glue);

                if (!$level && !$sqLevel && !$isString) {
                    $matches[] = trim(substr($expression, $offsetPiece, $gluePos - $offsetPiece));

                    $offsetPiece = $offset;
                }
            }
        }

        $matches[] = trim(substr($expression, $offsetPiece, $stringLength - $offsetPiece));

        return $matches;
    }
}
