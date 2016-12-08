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

class XPathAnd extends AbstractXPathLogic
{
    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        foreach ($this->getExpressions() as $expression) {
            if (!$expression->evaluate($context, $xPathReference)) {
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
