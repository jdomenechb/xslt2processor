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
use Jdomenechb\XSLT2Processor\XPath\Exception\ParameterNotValid;
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function node-name() from XSLT standard library.
 */
class NodeName extends AbstractFunctionImplementation
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
        $argument = $func->getParameters()[0]->evaluate($context);

        if (!$argument instanceof DOMNodeList || $argument->count() > 1) {
            throw new ParameterNotValid(
                1,
                static::class,
                [ParameterNotValid::TYPE_EMPTY_SEQUENCE, ParameterNotValid::TYPE_NODE]
            );
        }

        if (!$argument->count()) {
            return new DOMNodeList();
        }

        if ($argument->item(0)->nodeName[0] !== '#') {
            return $argument->item(0)->nodeName;
        }

        return new DOMNodeList();
    }
}
