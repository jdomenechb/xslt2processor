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
 * Function comment() from XSLT standard library.
 */
class Comment extends AbstractFunctionImplementation
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
        $result = new DOMNodeList();

        if (!$context instanceof DOMNodeList) {
            $context = new DOMNodeList($context);
        }

        foreach ($context as $contextNode) {
            foreach ($contextNode->childNodes as $childNode) {
                if (!$childNode instanceof \DOMComment) {
                    continue;
                }

                $result[] = $childNode;
            }
        }

        return $result;
    }
}
