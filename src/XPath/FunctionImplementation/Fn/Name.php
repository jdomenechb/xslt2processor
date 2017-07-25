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
 * Function name() from XSLT standard library.
 */
class Name extends AbstractFunctionImplementation
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
            $property = new DOMNodeList($context);
        } else {
            $property = $func->getParameters()[0]->evaluate($context);

            if (!$property instanceof DOMNodeList) {
                $property = new DOMNodeList($property);
            }
        }

        if (!$property->count()) {
            return null;
        }

        if ($property->item(0) instanceof \DOMElement) {
            return $property->item(0)->nodeName;
        }

        return '';
    }
}
