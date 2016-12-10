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
 * Helper class with methods to analyze and parse expressions
 *
 * @author jdomenechb
 */
class ExpressionParserHelper
{
    /**
     * Given an expression, a start delimiter and an end delimiter, returns a list of pieces from the expression given
     * in which the odd indexes are the expression parts contained in-between this symbols (but only the first level),
     * and the even and zero indexes the parts outside the delimiter symbols.
     * @param string $expression
     * @param string $start
     * @param string $end
     * @return string[] Values with odd indexes are parts outside the subexpression, values with even indexes the
     * subexpressions.
     */
    public function parseFirstLevelSubExpressions($expression, $start, $end)
    {
        $level = 0;
        $matches = [];

        $offset = 0;
        $offsetPiece = 0;
        $stringToLower = strtolower($expression);
        $stringLength = strlen($expression);
        $lengthSOperator = strlen($start);
        $lengthEOperator = strlen($end);

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

            // Calculate min
            $min = min($lParPos, $rParPos);

            if ($min === $stringLength) {
                $offset = $stringLength;
            } elseif ($min == $lParPos) {
                ++$level;

                if ($level === 1) {
                    $matches[] = trim(substr($expression, $offsetPiece, $lParPos - $offsetPiece));
                    $offsetPiece = $min + $lengthSOperator;
                }

                $offset = $min + $lengthSOperator;
            } elseif ($min == $rParPos) {
                --$level;

                if ($level === 0) {
                    $matches[] = trim(substr($expression, $offsetPiece, $rParPos - $offsetPiece));
                    $offsetPiece = $min + $lengthEOperator;
                }

                $offset = $min + $lengthEOperator;
            }
        }

        $matches[] = trim(substr($expression, $offsetPiece, $stringLength - $offsetPiece));

        return $matches;
    }

    /**
     * Given an expression, a start delimiter and an end delimiter, the method returns an string with all the level
     * numbers of the subexpression delimited by the start and end string.
     * @param string $expression
     * @param string $start
     * @param string $end
     * @return string The level string. For example, if a subexpression is not available in the main expression, the
     * method will return '0'. In the case of an expression like
     * 'expr1 or (expr2 and (expression1 and (expression2)) or (expresssion))', being the parenthesis the delimiters,
     * the returned string would be '012321210'.
     */
    public function subExpressionLevelAnalysis($expression, $start, $end)
    {
        $history = '0';
        $level = 0;
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

            // Decide depending which comes next
            $min = min($sPos, $ePos);

            if ($min === $expressionLength) {
                break;
            } elseif ($min === $sPos) {
                ++$level;

                if ($level > 9) {
                    throw new RuntimeException('Only expressions up to 10 levels are supported');
                }
            } elseif ($min === $ePos) {
                --$level;
            }

            $history .= $level;
            $offset = $min + 1;
        }

        return $history;
    }

    /**
     * Given an expresison, a delimiter of the literal and the escaped literal, the method returns an string with all
     * the level of the literals delimited by the given symbol.
     * @param string $expression
     * @param string $delimiter
     * @param string $escaped
     * @return string
     * @throws RuntimeException
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
}
