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

use DOMNode;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XPath\Expression\ExpressionParserHelper;

class XPathPathNode extends AbstractXPath
{
    const LIMIT_BY_ELEMENT = 1;
    const LIMIT_BY_TEXT = 2;
    const LIMIT_ALL = 3;

    /**
     * Memory-based cache for reusing values already calculated in query().
     *
     * @var array
     */
    protected static $queryCache = [];

    /**
     * @var string
     */
    protected $node;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var ExpressionInterface
     */
    protected $selector;

    public static function parseXPath($string)
    {
        $obj = new self();

        if (mb_strpos($string, '[') === false) {
            $obj->setNode($string);

            return $obj;
        }

        $expressionParserHelper = new ExpressionParserHelper();
        $pieces = $expressionParserHelper->parseFirstLevelSubExpressions($string, '[', ']');
        $obj->setNode(array_shift($pieces));

        return $obj;
    }

    public function toString()
    {
        return $this->getNode();
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        if (
            trim($this->getNode()) === ''
            || strpos(trim($this->getNode()), '*') === 0
            || strpos(trim($this->getNode()), '.') === 0
        ) {
            return;
        }

        $parts = explode(':', $this->getNode());

        if (count($parts) == 1) {
            $toSet = $prefix . ':' . $parts[0];
        } else {
            $toSet = $this->getNode();
        }

        $this->setNode($toSet);
    }

    protected function evaluateExpression ($context)
    {
        return $this->query($context);
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param string $node
     */
    public function setNode($node)
    {
        $this->node = $node;
    }

    public function query($context)
    {
        $nodeName = $this->getNode();

        // FIXME: SPL Hashes can be reused in memory
//        $key = sha1($nodeName . ':' . spl_object_hash($context));

//        if (isset(static::$queryCache[$key])) {
//            return static::$queryCache[$key];
//        }

        // Direct cases
        if (!$nodeName) {
            // Document
            if (
                $context instanceof DOMNodeList
                || $context instanceof \DOMNodeList
            ) {
                $item = $context->item(0);
            } else {
                $item = $context;
            }

            if ($item instanceof \DOMDocument) {
                $doc = $item;
            } else {
                $doc = $item->ownerDocument;
            }

            return new DOMNodeList($doc);
        }

        if ($nodeName === '.') {
            if (!$context instanceof DOMNodeList) {
                return new DOMNodeList($context);
            }

            return $context;
        }

        if ($nodeName === '..') {
            if ($context instanceof DOMNodeList) {
                $context = $context->item(0);
            }

            return new DOMNodeList(($context->parentNode ?: null));
        }

        $parts = explode(':', $nodeName);
        $globalContext = $this->getGlobalContext();

        if (isset($parts[1])) {
            list($namespacePrefix, $localName) = $parts;
        } else {
            $localName = $nodeName;
            $namespacePrefix = $globalContext->getDefaultNamespace();
        }

        $namespaces = $globalContext->getNamespaces();
        $namespace = $namespaces[$namespacePrefix];

        if (!$namespaces->offsetExists($namespacePrefix)) {
            throw new \RuntimeException('Namespace with prefix "' . $namespacePrefix . '" is not defined in context');
        }

        // Detect the nodes we are interested in
        $result = [];

        if ($context instanceof DOMNodeList) {
            $contextArray = $context->toArray();
            $contextChildsArray = [];

            foreach ($contextArray as $contextElement) {
                $contextChildsArray[] = new DOMNodeList(
                    $contextElement instanceof \DOMDocument ?
                    $contextElement->documentElement :
                    $contextElement->childNodes
                );
            }

            $contextChilds = new DOMNodeList();
            $contextChilds->merge(...$contextChildsArray);

            $contextChilds = $contextChilds->toArray();
        } elseif (
            $context instanceof \DOMElement || $context instanceof \DOMDocument
        ) {
            $contextChilds = $context->childNodes;
        } elseif ($context instanceof DOMResultTree) {
            $contextChilds = $context->getBaseNode()->childNodes;
        } else {
            $contextChilds = [];
        }

        foreach ($contextChilds as $childNode) {
            /* @var $childNode DOMNode */
            if (
                (
                    $localName === '*'
                    || (
                        $childNode->localName === $localName
                        && $childNode->namespaceURI === $namespace
                    )
                )
                && $childNode instanceof \DOMElement
            ) {
                $result[] = $childNode;
            }
        }

        $result = new DOMNodeList($result);

//        static::$queryCache[$key] = $result;

        return $result;
    }
}
