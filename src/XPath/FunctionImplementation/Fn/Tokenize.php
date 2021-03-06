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
 * Function fn:tokenize() from XSLT standard library.
 */
class Tokenize extends AbstractFunctionImplementation
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
        if ($context instanceof DOMNodeList && !$context->count()) {
            return new DOMNodeList();
        }

        $value = $func->getParameters()[0]->evaluate($context);
        $value = Converter::fromDOMToString($value);

        $pattern = $func->getParameters()[1]->evaluate($context);
        $pattern = Converter::fromDOMToString($pattern);

        $parts = preg_split('#' . $pattern . '#', $value);

        if ($parts) {
            if ($context instanceof DOMNodeList) {
                $context = $context->item(0);
            }

            $doc = $context instanceof \DOMDocument ? $context : $context->ownerDocument;

            foreach ($parts as &$part) {
                $part = $doc->createTextNode($part);
            }
        }

        return new DOMNodeList($parts);
    }
}
