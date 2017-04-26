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

use Jdomenechb\XSLT2Processor\XPath\Expression\Converter;
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function fn:unparsed-text() from XSLT standard library.
 */
class UnparsedText extends AbstractFunctionImplementation
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
        $fileName = $func->getParameters()[0]->evaluate($context);

        /** @var \DOMDocument $doc */
        $doc = $func->getGlobalContext()->getStylesheetStack()->top();

        $folder = dirname($doc->documentURI);

        // Fix for windows
        if (strpos($folder, 'file:/') === 0) {
            $folder = substr($folder, 6);
        }

        return file_get_contents($folder . '/' . $fileName);
    }
}
