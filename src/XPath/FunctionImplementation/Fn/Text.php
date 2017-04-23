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
 * Function text() from XSLT standard library.
 */
class Text extends AbstractFunctionImplementation
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
    {//TODO: Optimize
        $result = new DOMNodeList();
        $result->setSortable(false);

        if (!$context instanceof DOMNodeList) {
            $context = new DOMNodeList($context);
        }

        foreach ($context as $contextNode) {
            foreach ($contextNode->childNodes as $childNode) {
                if (!$childNode instanceof \DOMCharacterData) {
                    continue;
                }

                $result[] = $childNode;
            }
        }

        return $result;
    }
}
