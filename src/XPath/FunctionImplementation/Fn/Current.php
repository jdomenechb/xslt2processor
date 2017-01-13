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
 * Function current() from XSLT standard library.
 */
class Current extends AbstractFunctionImplementation
{
    /**
     * @var \SplStack
     */
    protected static $stack;

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
        if (static::getStack()->isEmpty()) {
            return $context;
        }

        return static::getStack()->top();
    }

    public static function getStack()
    {
        if (!static::$stack) {
            static::$stack = new \SplStack();
        }

        return static::$stack;
    }
}
