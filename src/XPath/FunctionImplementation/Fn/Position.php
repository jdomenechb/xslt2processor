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
 * Function position() from XSLT standard library.
 */
class Position extends AbstractFunctionImplementation
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
        if ($func->getTemplateContext()->getContextParent()) {
            $parent = $func->getTemplateContext()->getContextParent();
            $i = 0;

            foreach ($parent as $childNode) {
                if ($childNode instanceof \DOMNode) {
                    ++$i;

                    if ($childNode->isSameNode($context)) {
                        return $i;
                    }
                }
            }
        } else {
            // Iterate all siblings
            $parent = $context->parentNode;
            $i = 0;

            foreach ($parent->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    ++$i;

                    if ($childNode->isSameNode($context)) {
                        return $i;
                    }
                }
            }
        }

        throw new \RuntimeException('No position could be found for the node');
    }
}
