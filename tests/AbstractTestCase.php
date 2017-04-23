<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Abstract test from which all the tests should inherit from. Provides helpful methods for working with the test
 * suites.
 *
 * @author jdomemechb
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @param $method
     * @param $suffix
     *
     * @return string
     */
    protected function getBaseTestPath($method, $suffix)
    {
        return 'tests/data/' . str_replace(['\\', '::'], '/', $method) . '-' . $suffix;
    }

    /**
     * Get the path of a test stylesheet.
     *
     * @param $method
     * @param string $suffix
     *
     * @return string
     */
    protected function getXSLStylesheetPath($method, $suffix = '001')
    {
        return $this->getBaseTestPath($method, $suffix) . '.xsl';
    }

    /**
     * Get the path of a test XML.
     *
     * @param $method
     * @param string $suffix
     *
     * @return string
     */
    protected function getXMLPath($method, $suffix = '001')
    {
        return $this->getBaseTestPath($method, $suffix) . '.xsl';
    }
}
