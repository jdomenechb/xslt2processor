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
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function generate-id() from XSLT standard library.
 */
class GenerateId extends AbstractFunctionImplementation
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
        if (!count($func->getParameters())) {
            $value = $context;
        } else {
            $value = $func->getParameters()[0]->evaluate($context);
        }

        if ($value instanceof DOMNodeList) {
            if (!$value->count()) {
                return '';
            }

            $value = $value->item(0);
        }

        /* @var $value \DOMElement */
        return 'n' . sha1($value->getNodePath());
    }
}
