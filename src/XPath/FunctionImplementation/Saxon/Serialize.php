<?php

namespace Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Saxon;

use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Alias of function serialize() from XSLT standard library.
 */
class Serialize extends AbstractFunctionImplementation
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
        $obj = new \Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn\Serialize();
        return $obj->evaluate($func, $context);
    }
}