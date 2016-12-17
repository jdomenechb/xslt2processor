<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Template;

/**
 * Entity for a key in a XSL template.
 *
 * @author jdomenechb
 */
class Key
{
    private $match;
    private $use;

    public function getMatch()
    {
        return $this->match;
    }

    public function getUse()
    {
        return $this->use;
    }

    public function setMatch($match)
    {
        $this->match = $match;
    }

    public function setUse($use)
    {
        $this->use = $use;
    }
}
