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

use DOMCdataSection;
use DOMCharacterData;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList as OriginalDOMNodeList;
use DOMText;
use DOMXPath;
use ErrorException;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\Factory;
use Jdomenechb\XSLT2Processor\XPath\Template\Key;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;

class Processor
{
    /**
     * @var DOMDocument
     */
    protected $newXml;

    /**
     * @var DOMDocument
     */
    protected $stylesheet;

    /**
     * @var DOMDocument
     */
    protected $xml;

    /**
     * @var string
     */
    protected $defaultNamespace = 'default';

    /**
     * @var array
     */
    protected $namespaces = [
        'default' => null,
    ];

    /**
     * @var false
     */
    protected $removeXmlDeclaration = false;

    /**
     * @var string
     */
    protected $method = 'xml';

    /**
     * @return Template[]
     */
    protected $templates = [];

    /**
     * @var DOMXPath
     */
    protected $xPath;

    /**
     * @var DOMXPath
     */
    protected $newXPath;

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var bool
     */
    protected $logXPath = true;

    /**
     * @var bool
     */
    public static $debug = false;

    protected $debugIdentation = -1;

    /**
     * @var array
     */
    protected $cdataSectionElements = [];

    /**
     * @var array
     */
    protected $decimalFormats = [];

    /**
     * @var Key[]
     */
    protected $keys = [];

    /**
     * @var array
     */
    protected $templateParams = [];

    /**
     * Determines if the the template being processed right now is imported/included or not.
     * @var bool
     */
    protected $isImported = false;

    /**
     * XSLT version used in the document
     * @var type
     */
    protected $version = null;

    /**
     * CacheItemPool to be used for caching. If null, no caching will be performed
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * Constructor.
     * @param string|\DOMDocument $xslt Path of the XSLT file or DOMDocument containing the XSL stylesheet.
     * @param \DOMDocument $xml DOMDocument of the XML file to be transformed
     */
    public function __construct($xslt, \DOMDocument $xml)
    {
        $this->xml = $xml;

        if (is_string($xslt) && is_file($xslt)) {
            $this->stylesheet = new DOMDocument();
            $this->stylesheet->load($xslt);
            $this->filePath = $xslt;
        } else if ($xslt instanceof \DOMDocument) {
            $this->stylesheet = $xslt;

            if (is_file($xslt->documentURI)) {
                $this->filePath = $xslt->documentURI;
            }
        } else {
            throw new \RuntimeException('XSLT must be a file path or a DOMDocument');
        }
    }

    /**
     * Main function to be called to transform the source XML with the XSL stylesheet defined
     * @return string
     */
    public function transformXML()
    {
        // Set error handler to throw exception at any error during execution
        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
            // In case the error was suppressed with the @-operator, avi¡oid processing it
            if (0 === error_reporting()) {
                return false;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        // Set the basic things needed
        $this->namespaces = ['default' => null,];
        $this->newXml = new DOMDocument();
        $this->defaultNamespace = 'default';

        // TODO: Move to Factory xPath class
        // Prepare the xPath log in case it is desired to save xPaths
        if ($this->logXPath) {
            file_put_contents('xpath_log.txt', '');
        }

        // Process the base xslStylesheet node to be aware of everything the XSL stylesheet features
        $this->xslStylesheet($this->stylesheet->documentElement, $this->xml, $this->newXml);

        // Create a dummy template to be executed to start all the process
        $node = $this->stylesheet->createElement('init-template');
        $node->setAttribute('select', '/');

        $this->xslApplyTemplates($node, $this->xml, $this->newXml, true);

        // Restore back the error handler we had
        restore_error_handler();

        // Return the result according to the output parameters
        if ($this->method == 'xml') {
            return $this->removeXmlDeclaration && $this->newXml->documentElement ? $this->newXml->saveXML($this->newXml->documentElement) : $this->newXml->saveXML();
        }

        //TODO: Doctype

        return $this->newXml->saveHTML();
    }

    protected function xslStylesheet(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $this->namespaces['xsl'] = $node->namespaceURI;

        foreach ($node->attributes as $attribute) {
            // Default namespace
            if ($attribute->nodeName == 'xpath-default-namespace') {
                $this->namespaces['default'] = $attribute->nodeValue;
                continue;
            }

            // XSL version
            if ($attribute->nodeName == 'version') {
                $this->version = $attribute->nodeValue;
                continue;
            }

            //throw new \RuntimeException('xsl:stylesheet parameter "' . $attribute->nodeName . '" not implemented');
        }

        $xpath = new DOMXPath($node->ownerDocument);

        foreach ($xpath->query('namespace::*', $node) as $nsNode) {
            $this->namespaces[$nsNode->localName] = $nsNode->namespaceURI;
        }

        // Start processing the template
        $this->processChildNodes($node, $context, $newContext);
    }

    protected function processChildNodes(DOMNode $parent, DOMNode $context, DOMNode $newContext)
    {
        foreach ($parent->childNodes as $childNode) {
            // Ignore spaces
            if ($childNode instanceof DOMText && preg_match('#^\s*$#', $childNode->nodeValue)) {
                continue;
            }

            // Ignore comments
            if ($childNode instanceof DOMComment) {
                continue;
            }

            // Determine if it is an XSLT node or not
            if ($childNode->namespaceURI == $this->namespaces['xsl']) {
                $this->processXsltNode($childNode, $context, $newContext);
                continue;
            }

            if ($childNode instanceof DOMText) {
                $wNode = $this->getWritableNode($newContext);
                $wNode->nodeValue .= $childNode->nodeValue;
                continue;
            }

            $this->processNormalNode($childNode, $context, $newContext);
        }
    }

    protected function processNormalNode(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        if (static::$debug) {
            echo '<div style="border-left: 1px solid #555; border-top: 1px solid #555; border-bottom: 1px solid #555; padding-left: 2px; margin-left: 20px">';
            echo "<b>$node->nodeName</b><br>";
            echo 'Before';
            echo '<pre>' . htmlspecialchars($this->method == 'xml' ? $this->newXml->saveXML() : $this->newXml->saveHTML()) . '</pre>';
        }

        // Copy the node to the new document
        $doc = $newContext->ownerDocument ?: $newContext;
        $newNode = $doc->importNode($node);

        $nodesToDelete = [];

        $newNode = $newContext->appendChild($newNode);

        foreach ($newNode->childNodes as $child) {
            $nodesToDelete[] = $child;
        }

        foreach ($nodesToDelete as $child) {
            $newNode->removeChild($child);
        }

        // Process attributes
        $factory = new Factory();
        $newAttr = [];

        foreach ($newNode->attributes as $attribute) {
            $attrTemplate = $factory->createFromAttributeValue($attribute->nodeValue);
            $attrTemplate->setDefaultNamespacePrefix($this->namespaces);
            $attrTemplate->setVariableValues(array_merge($this->templateParams, $this->variables));

            $newAttr[$attribute->nodeName] = $attrTemplate->evaluate($context);
        }

        foreach ($newAttr as $attrName => $attr) {
            $newNode->setAttribute($attrName, $attr);
        }

        $this->processChildNodes($node, $context, $newNode);

        if (static::$debug) {
            echo 'After';
            echo '<pre>' . htmlspecialchars($this->method == 'xml' ? $this->newXml->saveXML() : $this->newXml->saveHTML()) . '</pre>';
            echo '</div>';
        }
    }

    protected function processXsltNode(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $methodName = 'xsl' . implode('', array_map('ucfirst', explode('-', substr($node->nodeName, 4))));

        if (static::$debug) {
            echo '<div style="border-left: 1px solid #555; border-top: 1px solid #555; border-bottom: 1px solid #555; padding-left: 2px; margin-left: 20px">';
            echo "<b>$methodName</b><br>";
            foreach ($node->attributes as $attribute) {
                echo '@' . $attribute->name . '=' . $attribute->value . ' --- ';
            }
            echo "<br>";
            echo 'Before';
            echo '<pre>' . htmlspecialchars($this->method == 'xml' ? $this->newXml->saveXML() : $this->newXml->saveHTML()) . '</pre>';
        }

        if (method_exists($this, $methodName)) {
            $this->$methodName($node, $context, $newContext);
            if (static::$debug) {
                echo 'After';
                echo '<pre>' . htmlspecialchars($this->method == 'xml' ? $this->newXml->saveXML() : $this->newXml->saveHTML()) . '</pre>';
                echo '</div>';
            }
        } else {
            throw new RuntimeException('The method ' . $methodName . ' does not exist');
        }
    }

    protected function xslOutput(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        foreach ($node->attributes as $attribute) {
            switch ($attribute->nodeName) {
                case 'omit-xml-declaration':
                    $this->removeXmlDeclaration = $attribute->nodeValue == 'yes';
                    break;

                case 'indent':
                    $this->newXml->formatOutput = $attribute->nodeValue == 'yes';
                    $this->newXml->preserveWhiteSpace = $attribute->nodeValue != 'yes';
                    break;

                case 'method':
                    $this->method = $attribute->nodeValue;
                    break;

                case 'encoding':
                    $this->newXml->encoding = $attribute->nodeValue;
                    break;

                case 'cdata-section-elements':
                    $elements = explode(' ', $attribute->nodeValue);
                    $this->cdataSectionElements = array_merge($this->cdataSectionElements, $elements);

                    break;

                case 'version':
                    // TODO: We do not care about version yet
                    break;

                default:
                    throw new RuntimeException($attribute->nodeName . ' attribute from xslOutput not supported');
            }
        }
    }

    protected function xslTemplate(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $template = new Template();
        $template->setNode($node);

        foreach ($node->attributes as $attribute) {
            $setter = 'set' . ucfirst($attribute->nodeName);

            if (method_exists($template, $setter)) {
                $template->$setter($attribute->nodeValue);
            }
        }

        // Priority
        if (!$node->hasAttribute('priority')) {
            $xPath = $node->getAttribute('match');

            if (in_array($xPath, ['*', '@*']) || preg_match('#^[a-z-]+\(\)$#', $xPath)) {
                $template->setPriority(-0.5);
            } elseif (preg_match('#^@?[a-z-]+:\*$#', $xPath)) {
                $template->setPriority(-0.25);
            } elseif (strpos($xPath, '/') === false && strpos($xPath, '[') === false) {
                $template->setPriority(0);
            } else {
                $template->setPriority(0.5);
            }
        }

        if (!$this->isImported) {
            $template->setPriority($template->getPriority() + 2);
        }

        $this->insertTemplate($template);
    }

    protected function getTemplatesByMatch($match)
    {
        return array_values(array_filter($this->templates, function (Template $value) use ($match) {
            return $value->getMatch() == $match;
        }));
    }

    protected function getTemplatesByName($name)
    {
        return array_values(array_filter($this->templates, function (Template $value) use ($name) {
            return $value->getName() == $name;
        }));
    }

    protected function processTemplate(Template $template, DOMNode $context, DOMNode $newContext, $params = [])
    {
        $currentParams = $this->templateParams;
        $this->templateParams = $params;

        $this->processChildNodes($template->getNode(), $context, $newContext);

        $this->templateParams = $currentParams;
    }

    protected function xslApplyTemplates(DOMNode $node, DOMNode $context, DOMNode $newContext, $first = false)
    {
        // Select the candidates to be processed
        $applyTemplatesSelect = $node->getAttribute('select');
        $applyTemplatesSelectParsed = $this->parseXPath($applyTemplatesSelect);

        $nodesMatched = $applyTemplatesSelectParsed->query($context);

        $fbPossibleTemplate = null;
        $executed = false;

        if (!$nodesMatched->length) {
            return;
        }

        // Select a template that match
        foreach ($this->templates as $template) {
            if (!$fbPossibleTemplate) {
                $fbPossibleTemplate = $template;
            }

            $xPath = $template->getMatch();

            if (!$xPath) {
                continue;
            }

            $mode = $template->getMode();

            if ($node->hasAttribute('mode') && $mode != $node->getAttribute('mode')) {
                continue;
            }

            $xPathParsed = $this->parseXPath($xPath);
            $results = $xPathParsed->query(!$nodesMatched->item(0) instanceof \DOMDocument? $nodesMatched->item(0)->parentNode : $nodesMatched->item(0));

            if ($results === false) {
                continue;
            }

            if (!$results instanceof OriginalDOMNodeList && !$results instanceof DOMNodeList) {
                throw new RuntimeException('xPath "' . $template->getMatch() . '" evaluation wrong: expected DOMNodeList');
            }

            if (!$results->count()) {
                continue;
            }

            $isMatch = false;

            foreach ($results as $possible) {
                foreach ($nodesMatched as $nodeMatched) {
                    if ($possible->isSameNode($nodeMatched)) {
                        $isMatch = true;
                        break 2;
                    }
                }
            }

            if ($isMatch) {
                foreach ($nodesMatched as $nodeMatched) {
                    $this->processTemplate($template, $nodeMatched, $newContext);
                    $executed = true;
                }

                return;
            }
        }

        // No matched templates: if first, select the most prioritary one
        if (!$first || $executed) {
            return;
        }

        if (!$fbPossibleTemplate) {
            throw new \RuntimeException('No template match found');
        }

        // Apply the template for the match
        $xPathProcessed = $this->parseXPath($fbPossibleTemplate->getMatch());
        $nodes = $xPathProcessed->query($context);

        foreach ($nodes as $contextNode) {
            $this->processTemplate($fbPossibleTemplate, $contextNode, $newContext);
        }
    }

    protected function xslIf(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        // Evaluate the if
        $toEvaluate = $node->getAttribute('test');
        $xPathParsed = $this->parseXPath($toEvaluate);
        $result = $xPathParsed->evaluate($context);

        if (
            $result === true
            || ((is_string($result) || is_float($result) || is_int($result)) && $result)
            || $result instanceof OriginalDOMNodeList && $result->length
            || $result instanceof DOMNodeList && $result->count()
        ) {
            $this->processChildNodes($node, $context, $newContext);

            return true;
        }

        return false;
    }

    protected function uniformXPath($xPath)
    {
        return $this->parseXPath($xPath)->toString();
    }

    protected function parseXPath($xPath)
    {
        $xPathParsed = null;
        $key = sha1($xPath);

        if ($this->getCache()) {
            $cacheItem = $this->getCache()->getItem($key);

            if ($cacheItem->isHit()) {
                $xPathParsed = $cacheItem->get();
            }
        }

        if (!$xPathParsed) {
            $factory = new Factory();
            $xPathParsed = $factory->create($xPath);
        }

        if ($this->getCache() && !$cacheItem->isHit()) {
            $cacheItem->set($xPathParsed);
            $this->getCache()->save($cacheItem);
        }

        $xPathParsed->setDefaultNamespacePrefix('default');
        $xPathParsed->setVariableValues(array_merge($this->variables, $this->templateParams));
        $xPathParsed->setNamespaces($this->namespaces);
        $xPathParsed->setKeys($this->keys);

        $transformed = $xPathParsed->toString();

        if ($this->logXPath) {
            file_put_contents('xpath_log.txt', $xPath . ' =====> ' . $transformed . "\n", FILE_APPEND);
        }

        return $xPathParsed;
    }

    protected function evaluateAttrValueTemplates($attrValue, $context)
    {
        $factory = new Factory();
        $xPathParsed = $factory->createFromAttributeValue($attrValue);
        $xPathParsed->setDefaultNamespacePrefix('default');
        $xPathParsed->setVariableValues($this->variables);

        return $xPathParsed->evaluate($context);
    }

    protected function xslValueOf(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        // Evaluate the value
        $toEvaluateXPath = $node->getAttribute('select');
        $toEvaluate = $this->parseXPath($toEvaluateXPath);
        $result = $toEvaluate->evaluate($context);

        if ($result === true) {
            $result = 'true';
        } elseif ($result === false) {
            $result = 'false';
        }

        //echo $toEvaluate . "<br/>\n";

        $wNode = $this->getWritableNode($newContext);

        if ($result instanceof OriginalDOMNodeList || $result instanceof DOMNodeList) {
            foreach ($result as $subResult) {
                $wNode->nodeValue .= $subResult->nodeValue;
            }
        } else {
            $wNode->nodeValue .= $result;
        }
    }

    protected function xslText(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        // Get the text
        $text = '';

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMText) {
                $text .= $childNode->nodeValue;
            }
        }

        $adoe = $node->getAttribute('disable-output-escaping');
        $wNode = $this->getWritableNode($newContext);

        if ($adoe != 'yes') {
            $wNode->nodeValue .= $text;
        } else {
            if ($wNode instanceof DOMText) {
                $wNode = $wNode->parentNode->appendChild($newContext->ownerDocument->createCDATASection(''));
                $wNode->nodeValue .= $text;
                $wNode->parentNode->appendChild($newContext->ownerDocument->createTextNode(''));
            } else {
                $wNode->nodeValue .= $text;
            }
        }
    }

    protected function xslCopyOf(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $selectXPath = $node->getAttribute('select');
        $selectParsed = $this->parseXPath($selectXPath);
        //$selectXPath = $this->uniformXPath($selectXPath);

        $results = $selectParsed->evaluate($context);

        if ($results instanceof OriginalDOMNodeList) {
            foreach ($results as $result) {
                foreach ($result->childNodes as $childNode) {
                    $childNode = $this->newXml->importNode($childNode);
                    $newContext->appendChild($childNode);
                }
            }
        } else {
            $wNode = $this->getWritableNode($newContext);
            $wNode->nodeValue .= $results;
        }
    }

    protected function getWritableNode($newContext)
    {
        $writableNode = $newContext;

        if (!$newContext->childNodes->length) {
            if (in_array($newContext->nodeName, $this->cdataSectionElements)) {
                $textNode = $newContext->ownerDocument->createCDATASection('');
            } else {
                /* @var $textNode \DOMText */
                $textNode = $newContext->ownerDocument->createTextNode('');
            }

            $newContext->appendChild($textNode);
        }

        if (
            $newContext->childNodes->item($newContext->childNodes->length - 1) instanceof DOMCdataSection
            || $newContext->childNodes->item($newContext->childNodes->length - 1) instanceof DOMText
        ) {
            $writableNode = $newContext->childNodes->item($newContext->childNodes->length - 1);
        } else {
            if (in_array($newContext->nodeName, $this->cdataSectionElements)) {
                $textNode = $newContext->ownerDocument->createCDATASection('');
            } else {
                $textNode = $newContext->ownerDocument->createTextNode('');
            }

            $writableNode = $newContext->appendChild($textNode);
        }

        return $writableNode;
    }

    protected function xslVariable(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');

        if ($node->hasAttribute('select')) {
            $selectXPath = $node->getAttribute('select');
            $results = $this->parseXPath($selectXPath)->evaluate($context);

            $this->variables[$name] = $results;
        } else {
            $this->variables[$name] = $this->evaluateBody($node, $context, $newContext);
        }
    }

    protected function xslChoose(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        foreach ($node->childNodes as $childNode) {
            // Ignore spaces
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->nodeName == 'xsl:when') {
                if ($this->xslIf($childNode, $context, $newContext)) {
                    if (static::$debug) {
                        echo 'Option chosen: xsl:when test="' . $childNode->getAttribute('test') . '"<br>';
                    }
                    break;
                }
            }

            if ($childNode->nodeName == 'xsl:otherwise') {
                if (static::$debug) {
                    echo 'Option chosen: xsl:otherwise';
                }

                $this->processChildNodes($childNode, $context, $newContext);
            }
        }
    }

    protected function xslImport(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        if (!$this->filePath) {
            throw new RuntimeException('The XSLT template must be loaded from a file for xsl:import to work');
        }

        $basePath = dirname($this->filePath);

        $href = $node->getAttribute('href');
        $href = $basePath . '/' . $href;

        $oldFilePath = $this->filePath;
        $this->filePath = $href;

        $importedXslt = new DOMDocument();
        $importedXslt->load($href);

        $wasImported = $this->isImported;
        $this->isImported = true;
        $this->xslStylesheet($importedXslt->documentElement, $context, $newContext);
        $this->isImported = $wasImported;

        $this->filePath = $oldFilePath;
    }

    protected function xslInclude(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        if (!$this->filePath) {
            throw new RuntimeException('The XSLT template must be loaded from a file for xsl:include to work');
        }

        $basePath = dirname($this->filePath);

        $href = $node->getAttribute('href');
        $href = $basePath . '/' . $href;

        $oldFilePath = $this->filePath;
        $this->filePath = $href;

        $importedXslt = new DOMDocument();
        $importedXslt->load($href);

        $wasImported = $this->isImported;
        $this->isImported = true;
        $this->xslStylesheet($importedXslt->documentElement, $context, $newContext);
        $this->isImported = $wasImported;

        $this->filePath = $oldFilePath;
    }

    protected function xslAttribute(DOMElement $node, DOMNode $context, DOMElement $newContext)
    {
        $name = $node->getAttribute('name');
        if ($node->hasAttribute('select')) {
            $selectXPath = $node->getAttribute('select');
            $selectXPathTransformed = $this->parseXPath($selectXPath);

            $results = $selectXPathTransformed->evaluate($context);

            $value = $results;
        } else {
            $value = $this->evaluateBody($node, $context, $newContext);
        }

        $newContext->setAttribute($name, $value);
    }

    protected function evaluateBody(DOMElement $node, DOMNode $context, DOMNode $newContext = null)
    {
        // Create temporal context
        $tmpContext = $this->xml->createElement('tmptmptmptmptmpevaluateBody' . rand(0, 9999999));

        $this->processChildNodes($node, $context, $tmpContext);

        if ($tmpContext->childNodes->length == 1) {
            if ($tmpContext->childNodes->item(0) instanceof DOMText) {
                return $tmpContext->childNodes->item(0)->nodeValue;
            }

            return new DOMNodeList($tmpContext->childNodes->item(0));
        } elseif ($tmpContext->childNodes->length > 1) {
            $allText = true;
            $result = '';

            foreach ($tmpContext->childNodes as $childNode) {
                if (!$childNode instanceof DOMCharacterData) {
                    $allText = false;
                    break;
                }

                $result .= $childNode->nodeValue;
            }

            if ($allText) {
                return $result;
            }

            return $tmpContext->childNodes;
        }
    }

    protected function xslForEach(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $xPath = $node->getAttribute('select');
        $xPathParsed = $this->parseXPath($xPath);
        $result = $xPathParsed->evaluate($context);

        if (!$result->length) {
            return;
        }

        // Detect sortings
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->nodeName != 'xsl:sort') {
                continue;
            }

            if (static::$debug) {
                echo 'xsl:sort ';

                foreach ($childNode->attributes as $attribute) {
                    echo '@' . $attribute->name . '=' . $attribute->value . ' --- ';
                }

                echo '<br/>';
            }

            if ($childNode->hasAttribute('select')) {
                $xPath = $childNode->getAttribute('select');
                $xPathParsed = $this->parseXPath($xPath);

                $newResults = $result->toArray();

                usort($newResults, function($a, $b) use ($xPathParsed) {
                    return strcmp(
                        $xPathParsed->evaluate($a)->item(0)->nodeValue,
                        $xPathParsed->evaluate($b)->item(0)->nodeValue
                    );
                });
            }

            break;
        }

        foreach ($result as $eachNode) {
            $this->processChildNodes($node, $eachNode, $newContext);
        }
    }

    protected function xslFunction(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $template = new CustomFunction();
        $template->setNode($node);
        $template->setContext($this);

        foreach ($node->attributes as $attribute) {
            $setter = 'set' . ucfirst($attribute->nodeName);

            if (method_exists($template, $setter)) {
                $template->$setter($attribute->nodeValue);
            }
        }

        XPathFunction::setCustomFunction($template);
    }

    protected function xslElement(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $namespace = $node->getAttribute('namespace');
        $name = $node->getAttribute('name');

        $namespace = $this->evaluateAttrValueTemplates($namespace, $context);

        $document = $newContext;

        if (!$document instanceof DOMDocument) {
            $document = $document->ownerDocument;
        }

        if ($namespace) {
            $newNode = $document->createElementNS($namespace, $name);
        } else {
            $newNode = $document->createElement($name);
        }

        $newContext->appendChild($newNode);

        $this->processChildNodes($node, $context, $newNode);
    }

    protected function xslSequence(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $this->xslCopyOf($node, $context, $newContext);
    }

    protected function xslAnalyzeString(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $regex = '#' . str_replace('#', '\#', $node->getAttribute('regex')) . '#';

        // Get the nodes to process
        $xPath = $node->getAttribute('select');
        $result = $this->parseXPath($xPath)->evaluate($context);

        // Get the nodes of the actions to apply
        $matchingNode = null;
        $nonMatchingNode = null;

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->nodeName == 'xsl:matching-substring') {
                $matchingNode = $childNode;
            } elseif ($childNode instanceof DOMElement && $childNode->nodeName == 'xsl:non-matching-substring') {
                $nonMatchingNode = $childNode;
            }
        }

        if (!$result instanceof OriginalDOMNodeList && !is_array($result)) {
            $result = [$result];
        }

        foreach ($result as $resultNode) {
            if ($resultNode instanceof DOMElement) {
                $text = $resultNode->nodeValue;
            } else {
                $text = $resultNode;
            }

            preg_match_all($regex, $text, $matches);

            if (!count($matches[0]) && $nonMatchingNode) {
                $this->xslMatchingOrNotMatchingSubstring($nonMatchingNode, $text, $newContext);
            } else {
                foreach ($matches[0] as $match) {
                    $nonMatchingText = substr($text, 0, strpos($text, $match));
                    $matchingText = substr($text, strpos($text, $match), strlen($match));

                    if ($nonMatchingNode) {
                        $this->xslMatchingOrNotMatchingSubstring($nonMatchingNode, $nonMatchingText, $newContext);
                    }

                    if ($matchingNode) {
                        $this->xslMatchingOrNotMatchingSubstring($matchingNode, $matchingText, $newContext);
                    }

                    $text = substr($text, strlen($nonMatchingText) + strlen($matchingText));
                }
            }
        }
    }

    protected function xslMatchingOrNotMatchingSubstring(DOMNode $node, $match, DOMNode $newContext)
    {
        $tmpContext = $this->xml->createElement('tmptmptmptmptmpmonmsubstring');
        $text = $this->getWritableNode($tmpContext);
        $text->nodeValue = $match;

        $this->processChildNodes($node, $tmpContext, $newContext);
    }

    protected function xslDecimalFormat(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $name = 'default';
        $info = [];

        foreach ($node->attributes as $attribute) {
            switch ($attribute->nodeName) {
                case 'name':
                    $name = $attribute->nodeValue;
                    break;

                case 'decimal-separator':
                    $info['decimal-separator'] = $attribute->nodeValue;
                    break;

                case 'grouping-separator':
                    $info['grouping-separator'] = $attribute->nodeValue;
                    break;

                default:
                    throw new RuntimeException('xsl:decimal-format attribute ' . $attribute->nodeName . ' not supported');
            }
        }

        $this->decimalFormats[$name] = $info;
    }

    protected function xslKey(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $key = new Key();
        $key->setMatch($node->getAttribute('match'));
        $key->setUse($node->getAttribute('use'));

        $this->keys[$node->getAttribute('name')] = $key;
    }

    protected function xslComment(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $value = $this->evaluateBody($node, $context, $newContext);
        /* @var $doc DOMDocument */
        $doc = $newContext->ownerDocument ?: $newContext;

        $comment = $doc->createComment($value);
        $newContext->appendChild($comment);
    }

    protected function xslCallTemplate(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $params = [];
        $name = $node->getAttribute('name');

        // Detect possible params
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->nodeName != 'xsl:with-param') {
                throw new RuntimeException('Found not recognized "' . $childNode->nodeName . '" inside xsl:call-template');
            }

            if (static::$debug) {
                echo 'xsl:with-param ';

                foreach ($childNode->attributes as $attribute) {
                    echo '@' . $attribute->name . '=' . $attribute->value . ' --- ';
                }

                echo '<br/>';
            }

            $childName = $childNode->getAttribute('name');

            if ($childNode->hasAttribute('select')) {
                $xPath = $childNode->getAttribute('select');
                $xPathParsed = $this->parseXPath($xPath);
                $result = $xPathParsed->evaluate($context);

                $params[$childNode->getAttribute('name')] = $result;
            } else {
                $params[$childNode->getAttribute('name')] = $this->evaluateBody($childNode, $context);
            }
        }

        // Select the candidates to be processed

        $templates = $this->getTemplatesByName($name);

        if (!count($templates)) {
            throw new RuntimeException('No templates by the name "' . $name . '" found');
        }

        if (count($templates) > 1) {
            throw new RuntimeException('Multiple templates by the name "' . $name . '" found');
        }

        $this->processTemplate($templates[0], $context, $newContext, $params);
    }

    protected function xslParam(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');

        if (!isset($this->templateParams[$name])) {
            if ($node->hasAttribute('select')) {
                $select = $node->getAttribute('select');
                $selectParsed = $this->parseXPath($select);
                $result = $selectParsed->evaluate($context);

                $this->templateParams[$name] = $result;
            } else {
                $this->templateParams[$name] = null;
            }
        }
    }

    protected function insertTemplate(Template $template)
    {
        for ($i = 0; $i < count($this->templates); $i++) {
            $currentTemplate = $this->templates[$i];

            if ($template->getPriority() > $currentTemplate->getPriority()) {
                $newTemplates = array_slice($this->templates, 0, $i);
                $newTemplates[] = $template;
                $newTemplates = array_merge($newTemplates, array_slice($this->templates, $i));

                $this->templates = $newTemplates;

                return;
            }
        }

        $this->templates[] = $template;
    }

    protected function xslSort(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        // xsl:sort is implemented inside the body of the functions that use it, so nothing to do here.
    }

    /**
     *
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }


}
