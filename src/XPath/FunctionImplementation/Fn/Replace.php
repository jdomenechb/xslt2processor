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
 * Function replace() from XSLT standard library,
 * @package Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn
 */
class Replace extends AbstractFunctionImplementation
{
    /**
     * @inheritdoc
     * @param XPathFunction $func
     * @param $context
     * @return string
     */
    public function evaluate(XPathFunction $func, $context)
    {
        $value = $func->getParameters()[0]->evaluate($context);
        $value = $this->valueAsString($value);

        $pattern = $func->getParameters()[1]->evaluate($context);
        $pattern = $this->valueAsString($pattern);

        $replacement = $func->getParameters()[2]->evaluate($context);
        $replacement = $this->valueAsString($replacement);

        $value = preg_replace('#' . str_replace('#', '\#', $pattern) . '#', $replacement, $value);

        return $value;
    }
}