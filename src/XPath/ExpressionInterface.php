<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 9:39
 */

namespace Jdomenechb\XSLT2Processor\XPath;


interface ExpressionInterface
{
    public function parse($string);

    public function toString();

    public function setDefaultNamespacePrefix($prefix);

    public function setVariableValues(array $values);

    public function evaluate(\DOMNode $context, \DOMXPath $xPathReference);
}