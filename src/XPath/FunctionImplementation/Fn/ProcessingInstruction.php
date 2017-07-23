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
use Jdomenechb\XSLT2Processor\XPath\Expression\Converter;
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function fn:processing-instruction() from XSLT standard library.
 */
class ProcessingInstruction extends AbstractFunctionImplementation
{
    /**
     * {@inheritdoc}
     *
     * @param XPathFunction $func
     * @param $context
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function evaluate(XPathFunction $func, $context)
    {
        if (!$context || ($context instanceof DOMNodeList && !$context->count())) {
            return new DOMNodeList();
        }

        $contextDom = Converter::fromDOMToDOMDocument($context);
        $xPath = new \DOMXPath($contextDom);

        $funcString = $func->toString();

        // We should strip the prefix
        $funcString = substr($funcString, strpos($funcString, ':') + 1);

        return new DOMNodeList($xPath->evaluate($funcString, $context->item(0)));
    }
}
