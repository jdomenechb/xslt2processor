<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath;

use Jdomenechb\XSLT2Processor\XPath\Exception\InvalidEvaluation;

/**
 * Represents a xPath string.
 *
 * @author jdomenechb
 */
class XPathString extends AbstractXPath
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @var bool
     */
    protected $doubleQuoted;

    /**
     * {@inheritdoc}
     *
     * The given xPath must be enclosed in simple quotes to be considered an string.
     */
    public static function parseXPath($xPath)
    {
        $eph = new Expression\ExpressionParserHelper();

        if (
            (
                // Starts with single quote
                mb_strpos($xPath, "'") !== 0
                // Ends with single quote
                || mb_substr($xPath, -1) !== "'"
                // Does not contain other quotes inside
                || strpos(substr($eph->literalLevelAnalysis($xPath, "'", "''"), 1, -1), '0') !== false
            )
            && (
                // Starts with double quote
                mb_strpos($xPath, '"') !== 0
                // Ends with double quote
                || mb_substr($xPath, -1) !== '"'
                // Does not contain other quotes inside
                || strpos(substr($eph->literalLevelAnalysis($xPath, '"', '""'), 1, -1), '0') !== false
            )
        ) {
            return false;
        }

        $obj = new self;


        if ($xPath[0] === "'") {
            $obj->setString(str_replace("''", "'", mb_substr($xPath, 1, -1)));
            $obj->setDoubleQuoted(false);
        } else {
            $obj->setString(str_replace('""', '"', mb_substr($xPath, 1, -1)));
            $obj->setDoubleQuoted(true);
        }

        return $obj;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return $this->isDoubleQuoted() ?
            '"' . str_replace('"', '""', $this->getString()) . '"' :
            "'" . str_replace("'", "''", $this->getString()) . "'";
    }

    /**
     * {@inheritdoc}
     * @throws InvalidEvaluation
     */
    public function evaluate($context)
    {
        if ($this->getString() === null) {
            throw new InvalidEvaluation('Not a valid string evaluation');
        }

        return (string) $this->getString();
    }

    /**
     * @return mixed
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @return bool
     */
    public function isDoubleQuoted()
    {
        return $this->doubleQuoted;
    }

    /**
     * @param bool $doubleQuoted
     */
    protected function setDoubleQuoted($doubleQuoted)
    {
        $this->doubleQuoted = $doubleQuoted;
    }

    /**
     * @param string $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }
}
