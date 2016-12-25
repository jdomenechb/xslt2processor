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
 * Function substring() from XSLT standard library.
 */
class Substring extends AbstractFunctionImplementation
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

        $start = $func->getParameters()[1]->evaluate($context) - 1;

        if ($start < 0) {
            $start = 0;
        }

        if (isset($func->getParameters()[2])) {
            $len = $func->getParameters()[2]->evaluate($context);

            return mb_substr($value, $start, $len);
        }

        return mb_substr($value, $start);
    }
}
