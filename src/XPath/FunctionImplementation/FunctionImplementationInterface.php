<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\FunctionImplementation;

use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Interface that defines a base function implementation object.
 */
interface FunctionImplementationInterface
{
    /**
     * Evaluate the function by the given application context and node context.
     *
     * @param XPathFunction $func
     * @param $context
     *
     * @return mixed
     */
    public function evaluate(XPathFunction $func, $context);
}
