<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn;

use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function format-number() from XSLT standard library.
 */
class FormatNumber extends AbstractFunctionImplementation
{
    /**
     * {@inheritdoc}
     *
     * @param XPathFunction $func
     * @param $context
     *
     * @return string
     */
    public function evaluate(XPathFunction $func, $context)
    {
        if (count($func->getParameters()) > 2) {
            throw new \RuntimeException('format-number function with more than 2 parameters not implemented yet');
        }

        $number = $func->getParameters()[0]->evaluate($context);

        $format = $func->getParameters()[1]->evaluate($context);
        $format = $this->valueAsString($format);

        $integerPart = (int) $number;
        $decimalPart = $number - $integerPart;

        if ($format == '') {
            return '';
        }

        $formatParts = explode('.', $format);

        if (count($formatParts) > 1) {
            throw new \RuntimeException('format-number function with decimal format not implemented yet');
        } else {
            $formatParts[1] = '';
        }

        $j = 1;
        $finalNumber = '';

        if (mb_substr($formatParts[0], .1) === '%') {
            $number *= 100;
            $finalNumber = '%';
        }

        $number = round($number, mb_strlen($formatParts[1]));

        $integerPart = (int) $number;
        $decimalPart = $number - $integerPart;

        for ($i = mb_strlen($formatParts[0]) - 1; $i >= 0; --$i) {
            // Percent
            if ($i === mb_strlen($formatParts[0]) - 1 && $formatParts[0][$i] === '%') {
                continue;
            }

            // Separators
            if (!in_array($formatParts[0][$i], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, '#'])) {
                $finalNumber = $formatParts[0][$i] . $finalNumber;
                continue;
            }

            $numberPart = (int) (($integerPart % ($j * 10)) / $j);

            if (
                $numberPart === 0 &&
                (
                    $formatParts[0][$i] == 0
                    || ($formatParts[0][$i] == '#' && ((int) $integerPart / $j / 10) > 0)
                )
            ) {
                $finalNumber = '0' . $finalNumber;
            } elseif ($numberPart !== 0) {
                $finalNumber = $numberPart . $finalNumber;
            }

            $j *= 10;
        }

        if (($rest = (int) ($integerPart / $j)) > 0) {
            $finalNumber = $rest . $finalNumber;
        }

        return $finalNumber;
    }
}
