<?php

namespace Jdomenechb\XSLT2Processor\XSLT;

/**
 * System properties for XSLT Processor
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
