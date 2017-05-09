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
 * Function fn:last() from XSLT standard library.
 */
class Last extends AbstractFunctionImplementation
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
        $last = $context;
        $current = $context;

        while ($current->nextSibling !== null) {
            $current = $current->nextSibling;

            if ($current->nodeName === $context->nodeName) {
                $last = $current;
            }
        }

        $fnPosition = new Position();

        return $fnPosition->evaluate($func, $last);
    }
}
