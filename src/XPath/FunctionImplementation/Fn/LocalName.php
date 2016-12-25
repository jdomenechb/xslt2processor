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
 * Function local-name() from XSLT standard library.
 */
class LocalName extends AbstractFunctionImplementation
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
        if (count($func->getParameters()) > 0) {
            $property = $func->getParameters()[0]->evaluate($context);

            if (!$property->count()) {
                $result = '';
            } else {
                $result = $property->item(0)->localName;
            }
        } else {
            $result = $context->localName;
        }

        return $result;
    }
}
