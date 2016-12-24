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
 * Function substring-before() from XSLT standard library,
 * @package Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn
 */
class SubstringBefore extends AbstractFunctionImplementation
{
    /**
     * @inheritdoc
     * @param XPathFunction $func
     * @param $context
     * @return string
     */
    public function evaluate(XPathFunction $func, $context)
    {
        $haystack = $func->getParameters()[0]->evaluate($context);
        $haystack = $this->valueAsString($haystack);

        $needle = $func->getParameters()[1]->evaluate($context);
        $needle = $this->valueAsString($needle);

        if (($pos = mb_strpos($haystack, $needle)) === false) {
            return '';
        }

        return mb_substr($haystack, 0, $pos);
    }
}