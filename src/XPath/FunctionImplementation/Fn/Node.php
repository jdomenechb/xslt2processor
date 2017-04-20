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
 * Function node() from XSLT standard library.
 */
class Node extends AbstractFunctionImplementation
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
        if (!$context instanceof DOMNodeList) {
            $context = new DOMNodeList($context);
        }

        $toReturn = new DOMNodeList();

        foreach ($context as $node) {
            $toReturn->merge(new DOMNodeList($node->childNodes));
        }

        return $toReturn;
    }
}
