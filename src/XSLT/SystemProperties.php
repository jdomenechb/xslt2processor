<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT;

/**
 * System properties for XSLT Processor.
 *
 * @author jordidomenech
 */
class SystemProperties
{
    protected static $properties = [
        'xsl:vendor' => 'Avow',
        'xsl:version' => '0.1',
    ];

    public static function getProperty($property)
    {
        if (isset(static::$properties[$property])) {
            return static::$properties[$property];
        }

        return '';
    }
}
