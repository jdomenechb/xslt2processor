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
use Jdomenechb\XSLT2Processor\XSLT\SystemProperties;

/**
 * Function system-property from XSLT standard library.
 */
class SystemProperty extends AbstractFunctionImplementation
{
    public function evaluate(XPathFunction $func, $context)
    {
        $property = $func->getParameters()[0]->evaluate($context);
        $property = $this->valueAsString($property);

        return SystemProperties::getProperty($property);
    }
}
