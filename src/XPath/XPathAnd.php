<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 10:02
 */

namespace Jdomenechb\XSLT2Processor\XPath;


class XPathAnd extends AbstractXPathLogic
{
    protected function getOperator()
    {
        return 'and';
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        foreach ($this->getExpressions() as $expression) {
            if (!$expression->evaluate($context, $xPathReference)) {
                return false;
            }
        }

        return true;
    }
}