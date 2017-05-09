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
 * Function translate() from XSLT standard library.
 */
class Translate extends AbstractFunctionImplementation
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
        $value = $func->getParameters()[0]->evaluate($context);
        $value = $this->valueAsString($value);

        $from = $func->getParameters()[1]->evaluate($context);
        $from = $this->valueAsString($from);

        $to = $func->getParameters()[2]->evaluate($context);
        $to = $this->valueAsString($to);

        $length = mb_strlen($value);
        $lengthFrom = mb_strlen($from);
        $lengthTo = mb_strlen($to);

        $result = '';
        $piecesFrom = [];
        $piecesTo = [];

        for ($j = 0; $j < $lengthFrom; $j++) {
            $piecesFrom[] = mb_substr($from, $j, 1);
        }

        for ($j = 0; $j < $lengthTo; $j++) {
            $piecesTo[] = mb_substr($to, $j, 1);
        }

        if (!$piecesTo) {
            $piecesTo = [''];
        }

        for ($i = 0; $i < $length; $i++) {
            $character = mb_substr($value, $i, 1);

            $key = array_search($character, $piecesFrom, true);

            if ($key !== false) {
                $result .= $piecesTo[$key];
            } else {
                $result .= $character;
            }
        }

        return $result;
    }
}
