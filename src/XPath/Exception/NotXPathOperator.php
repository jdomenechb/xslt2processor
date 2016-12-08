<?php
/**
 * Created by PhpStorm.
 * User: jdomeneb
 * Date: 23/09/2016
 * Time: 11:18
 */

namespace Jdomenechb\XSLT2Processor\XPath\Exception;


class NotXPathOperator extends \RuntimeException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('Not an operator');
    }
}