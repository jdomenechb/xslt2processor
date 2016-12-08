<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 10:02
 */

namespace Jdomenechb\XSLT2Processor\XPath;


class XPathOr extends AbstractXPathLogic
{
    protected function getOperator()
    {
        return 'or';
    }

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference)
    {
        foreach ($this->getExpressions() as $expression) {
            if ($expression->evaluate($context, $xPathReference)) {
                return true;
            }
        }

        return false;
    }
}