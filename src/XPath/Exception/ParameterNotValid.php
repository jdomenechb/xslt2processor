<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\Exception;

use RuntimeException;

class ParameterNotValid extends RuntimeException
{
    const TYPE_NODE = 'node';
    const TYPE_EMPTY_SEQUENCE = 'empty sequence';

    /**
     * {@inheritdoc}
     */
    public function __construct($number, $functionName, array $expected)
    {
        $msg = 'Parameter #' . $number . ' not valid on function ' . $functionName . ': expected '
            . implode(' or ', $expected);

        parent::__construct($msg);
    }
}
