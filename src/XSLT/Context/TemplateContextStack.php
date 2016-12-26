<?php

/**
 * This file is part of the XSLT2processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Context;


class TemplateContextStack extends \SplStack
{
    public function __construct()
    {
        $this->push(new TemplateContext());
    }

    public function pushAClone()
    {
        $this->push(clone $this->top());
    }
}