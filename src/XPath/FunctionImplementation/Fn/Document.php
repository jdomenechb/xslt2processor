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
 * Function document() from XSLT standard library.
 */
class Document extends AbstractFunctionImplementation
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
        // TODO: Implement parameter 2
        // TODO: Implement other values rather than empty string

        $firstParam = $func->getParameters()[0]->evaluate($context);
        $secondParam = null;

        if ($firstParam === '' && $secondParam === null) {
            // In the case of the an empty string as first param an no second param provided, we are returning a
            // DOMNodeList containing the document
            if ($context instanceof DOMNodeList) {
                $context = $context->item(0);
            }

            return new DOMNodeList($context instanceof \DOMDocument ? $context: $context->ownerDocument);
        }

        throw new \RuntimeException('The current use of the fn:document function is not implemented yet');

    }
}
