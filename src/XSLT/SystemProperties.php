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
    /**
     * List of static properties available
     * @var type
     */
    protected static $properties = [
        'xsl:vendor' => 'jdomenechb/xslt2processor',
        'xsl:version' => '0.1',
    ];

    /**
     * Returns the value of the property requested.
     * @param string $property
     * @return string Empty string if the requested property does not exist.
     */
    public static function getProperty($property)
    {
        if (isset(static::$properties[$property])) {
            return static::$properties[$property];
        }

        return '';
    }
}
