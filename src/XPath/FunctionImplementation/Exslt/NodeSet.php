<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Exslt;


use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function node-set from EXSLT library,
 * @package Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn
 */
class NodeSet extends AbstractFunctionImplementation
{
    /**
     * @inheritdoc
     * @param XPathFunction $func
     * @param $context
     * @return string
     */
    public function evaluate(XPathFunction $func, $context)
    {
        $property = $func->getParameters()[0]->evaluate($context);
        $property->setParent(true);

        $result = $property;

        return $result;
    }
}