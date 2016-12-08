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
}
