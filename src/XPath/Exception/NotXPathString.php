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

class NotXPathString extends RuntimeException
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('Not a string');
    }
}
