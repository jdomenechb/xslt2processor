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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function number() from XSLT standard library.
 */
class Number extends AbstractFunctionImplementation
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
        if (count($func->getParameters()) === 0) {
            $toEvaluate = $context;
        } else {
            $toEvaluate = $func->getParameters()[0]->evaluate($context);
        }

        if ($toEvaluate instanceof DOMNodeList) {
            if (!$toEvaluate->count()) {
                return NAN;
            }

            $toEvaluate = $toEvaluate->item(0);
        }

        if ($toEvaluate instanceof \DOMNode) {
            $toEvaluate = $toEvaluate->nodeValue;
        }

        if ($toEvaluate instanceof DOMResultTree) {
            $toEvaluate = $toEvaluate->evaluate();
        }

        if (!is_string($toEvaluate) || $toEvaluate !== '' || is_numeric($toEvaluate)) {
            return (float) $toEvaluate;
        }

        return NAN;
    }
}
