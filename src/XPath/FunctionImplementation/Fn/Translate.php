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

        return str_replace(str_split($from), str_split($to), $value);
    }
}
