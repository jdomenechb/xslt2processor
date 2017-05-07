<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath;

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;

class XPathAnd extends AbstractXPathLogic
{
    public function evaluate($context)
    {
        foreach ($this->getExpressions() as $expression) {
            $evaluation = $expression->evaluate($context);

            if (
                (!$evaluation instanceof DOMNodeList && !$evaluation)
                || ($evaluation instanceof DOMNodeList && !$evaluation->count())
            ) {
                return false;
            }
        }

        return true;
    }

    protected function getOperator()
    {
        return 'and';
    }
}
