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

use DOMCharacterData;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList as OriginalDOMNodeList;
use DOMText;
use DOMXPath;
use ErrorException;
use Jdomenechb\XSLT2Processor\XML\DOMElementUtils;
use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\Factory;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContextStack;
use Jdomenechb\XSLT2Processor\XSLT\Exception\MessageTerminatedException;
use Jdomenechb\XSLT2Processor\XSLT\Template\Key;
use Jdomenechb\XSLT2Processor\XSLT\Template\Template;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;

class Processor
{
    /**
     * @var Debug
     */
    protected $debug;

    /**
     * The DOMDocument that represents the transformed document.
     *
     * @var DOMDocument
     */
    protected $newXml;

    /**
     * The DOMDocument that represents the stylesheet.
     *
     * @var DOMDocument
     */
    protected $stylesheet;

    /**
     * The DOMDocument that represents the original XML.
     *
     * @var DOMDocument
     */
    protected $xml;

    /**
     * Contains information about how the output should be formatted.
     *
     * @var Output
     */
    protected $output;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var bool
     */
    protected $logXPath = false;

    /**
     * @var array
     */
    protected $decimalFormats = [];

    /**
     * Determines if the the template being processed right now is imported/included or not.
     *
     * @var bool
     */
    protected $isImported = false;

    /**
     * XSLT version used in the document.
     *
     * @var float
     */
    protected $version;

    /**
     * CacheItemPool to be used for caching. If null, no caching will be performed.
     *
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var GlobalContext
     */
    protected $globalContext;

    /**
     * @var TemplateContextStack
     */
    protected $templateContextStack;

    /**
     * @var \ArrayObject
     */
    protected $messages;

    /**
     * Constructor.
     *
     * @param string|\DOMDocument $xslt path of the XSLT file or DOMDocument containing the XSL stylesheet
     * @param \DOMDocument        $xml  DOMDocument of the XML file to be transformed
     */
    public function __construct($xslt, \DOMDocument $xml)
    {
        $this->xml = $xml;

        if (is_string($xslt) && is_file($xslt)) {
            $this->stylesheet = new DOMDocument();
            $this->stylesheet->load($xslt);
            $this->filePath = $xslt;
        } elseif ($xslt instanceof \DOMDocument) {
            $this->stylesheet = $xslt;

            if (is_file($xslt->documentURI)) {
                $this->filePath = $xslt->documentURI;
            } elseif (strpos($xslt->documentURI, 'file:/') === 0) {
                $this->filePath = substr($xslt->documentURI, 6);
            }
        } else {
            throw new \RuntimeException('XSLT must be a file path or a DOMDocument');
        }

        $this->setDebug(Debug::getInstance());
        $this->getDebug()->setOutput($this->getOutput());
    }

    /**
     * Main function to be called to transform the source XML with the XSL stylesheet defined.
     *
     * @return string
     */
    public function transformXML()
    {
        // Set error handler to throw exception at any error during execution
        set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
            // In case the error was suppressed with the @-operator, avoid processing it
            if (0 === error_reporting()) {
                return false;
            }

            if ($errno == E_USER_WARNING || $errno == E_USER_NOTICE) {
                return false;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        // Set the basic things needed
        $this->globalContext = new GlobalContext();
        $this->newXml = new DOMDocument();
        $this->messages = new \ArrayObject();

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

        try {
            $this->xslApplyTemplates($node, $this->xml, $this->newXml, true);
        } catch (MessageTerminatedException $ex) {
            trigger_error('Template execution was terminated because of an xsl:message');
        } catch (\Exception $exception) {
            echo htmlentities($this->newXml->saveXML());
            throw $exception;
        }

        // Restore back the error handler we had
        restore_error_handler();

        // Return the result according to the output parameters
        if ($this->getOutput()->getMethod() == Output::METHOD_XML) {
            return $this->getOutput()->getRemoveXmlDeclaration() && $this->newXml->documentElement ?
                $this->newXml->saveXML($this->newXml->documentElement) :
                $this->newXml->saveXML();
        }

        // Add missing HTML default tags
        $content = $this->getOutput()->getDoctype() . "\n";

        $headerXPath = $this->parseXPath('/html/head');
        $header = $headerXPath->query($this->newXml);

        if ($header->count()) {
            $meta = $this->getOutput()->getMetaCharsetTag($this->newXml);

            if ($header->item(0)->hasChildNodes()) {
                $header->item(0)->insertBefore($meta, $header->item(0)->childNodes->item(0));
            } else {
                $header->item(0)->appendChild($meta);
            }
        } else {
            throw new \RuntimeException('If the HTML does not implement the head tag, the output cannot be shown');
        }

        $result = $content . $this->newXml->saveHTML();

        return $result;
    }

    /**
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

    public function setLogXPath($value)
    {
        $this->logXPath = $value;
    }

    /**
     * Returns the Output class that determines the format of the transformation.
     *
     * @return Output
     */
    public function getOutput()
    {
        if (!$this->output) {
            $this->output = new Output();
        }

        return $this->output;
    }

    /**
     * Sets the Output class that determines the format of the transformation.
     *
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     * @return Debug
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param Debug $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return GlobalContext
     */
    public function getGlobalContext()
    {
        return $this->globalContext;
    }

    /**
     * @return TemplateContextStack
     */
    public function getTemplateContextStack()
    {
        if ($this->templateContextStack === null) {
            $this->templateContextStack = new TemplateContextStack();
        }

        return $this->templateContextStack;
    }

    /**
     * @param TemplateContextStack $templateContextStack
     */
    public function setTemplateContextStack($templateContextStack)
    {
        $this->templateContextStack = $templateContextStack;
    }

    /**
     * @return \ArrayObject
     */
    public function getMessages()
    {
        return $this->messages;
    }

    protected function xslStylesheet(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $this->getGlobalContext()->getNamespaces()[GlobalContext::NAMESPACE_XSL] = $node->namespaceURI;

        foreach ($node->attributes as $attribute) {
            // Default namespace
            if ($attribute->nodeName == 'xpath-default-namespace') {
                $this->getGlobalContext()->getNamespaces()[$this->getGlobalContext()->getDefaultNamespace()] = $attribute->nodeValue;
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
            $this->getGlobalContext()->getNamespaces()[$nsNode->localName] = $nsNode->namespaceURI;
        }

        // Start processing the template
        $this->processChildNodes($node, $context, $newContext);
    }

    protected function processChildNodes(DOMNode $parent, DOMNode $context, DOMNode $newContext)
    {
        $domElementUtils = new DOMElementUtils();

        foreach ($parent->childNodes as $childNode) {
            // Ignore spaces
            if ($childNode instanceof DOMText && trim($childNode->nodeValue) === '') {
                continue;
            }

            // Ignore comments
            if ($childNode instanceof DOMComment) {
                continue;
            }

            // Determine if it is an XSLT node or not
            if ($childNode->namespaceURI === $this->getGlobalContext()->getNamespaces()['xsl']) {
                $this->processXsltNode($childNode, $context, $newContext);
                continue;
            }

            if ($childNode instanceof DOMText) {
                $wNode = $domElementUtils->getWritableNodeIn($newContext, $this->getOutput()->getCdataSectionElements());
                $wNode->nodeValue .= $childNode->nodeValue;
                continue;
            }

            $this->processNormalNode($childNode, $context, $newContext);
        }
    }

    protected function processNormalNode(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $this->getDebug()->startNodeLevel($this->newXml, $node);

        if ($this->getTemplateContextStack()->count() === 1) {
            $this->getDebug()->printText('Node ignored due to being executed in xsl:stylesheet');
        } else {
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
            $newAttr = [];

            foreach ($newNode->attributes as $attribute) {
                $newAttr[$attribute->nodeName] = $this->evaluateAttrValueTemplates($attribute->nodeValue, $context);
            }

            foreach ($newAttr as $attrName => $attr) {
                $newNode->setAttribute($attrName, $attr);
            }

            $this->processChildNodes($node, $context, $newNode);
        }

        $this->getDebug()->endNodeLevel($this->newXml);
    }

    protected function processXsltNode(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $methodName = 'xsl' . implode('', array_map('ucfirst', explode('-', $node->localName)));

        $this->getDebug()->startNodeLevel($this->newXml, $node);

        if (method_exists($this, $methodName)) {
            $this->$methodName($node, $context, $newContext);
        } else {
            throw new RuntimeException('The XSL tag  ' . $node->nodeName . ' is not supported yet');
        }

        $this->getDebug()->endNodeLevel($this->newXml);
    }

    protected function xslOutput(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // Avoid for now processing outputs with name
        if ($node->hasAttribute('name')) {
            trigger_error(
                'xsl:output with attribute "name" is not supported, the whole tag will be ignored',
                E_USER_WARNING
            );

            return;
        }

        foreach ($node->attributes as $attribute) {
            switch ($attribute->nodeName) {
                case 'omit-xml-declaration':
                    $this->getOutput()->setRemoveXmlDeclaration($attribute->nodeValue === 'yes');
                    break;

                case 'indent':
                    $this->newXml->formatOutput = $attribute->nodeValue === 'yes';
                    $this->newXml->preserveWhiteSpace = $attribute->nodeValue !== 'yes';
                    break;

                case 'method':
                    $this->getOutput()->setMethod($attribute->nodeValue);
                    break;

                case 'encoding':
                    $this->newXml->encoding = $attribute->nodeValue;
                    break;

                case 'cdata-section-elements':
                    $elements = explode(' ', $attribute->nodeValue);
                    $this->getOutput()->setCdataSectionElements(array_merge(
                        $this->getOutput()->getCdataSectionElements(),
                        $elements
                    ));

                    break;

                case 'version':
                    $this->getOutput()->setVersion($attribute->nodeValue);
                    break;

                case 'doctype-public':
                    $this->getOutput()->setDoctypePublicAttribute($attribute->nodeValue);
                    break;

                case 'doctype-system':
                    $this->getOutput()->setDoctypeSystemAttribute($attribute->nodeValue);
                    break;

                default:
                    trigger_error($attribute->nodeName . ' attribute from xslOutput not supported', E_USER_WARNING);
            }
        }
    }

    protected function xslTemplate(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $template = new Template();
        $template->setNode($node);

        foreach ($node->attributes as $attribute) {
            $setter = 'set' . ucfirst($attribute->nodeName);

            if (method_exists($template, $setter)) {
                $template->$setter($attribute->nodeValue);
            }
        }

        // Set the priority by default if not defined
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

        $this->getGlobalContext()->getTemplates()->appendTemplate($template);
    }

    protected function processTemplate(Template $template, DOMNode $context, DOMNode $newContext, $params = [])
    {
        $newTContext = $this->getTemplateContextStack()->top();

        $newTContext->setVariables(new \ArrayObject(array_merge($newTContext->getVariables()->getArrayCopy(), $params)));
        $newTContext->setVariablesDeclaredInContext(new \ArrayObject(array_keys($params)));

        $this->processChildNodes($template->getNode(), $context, $newContext);
    }

    protected function xslApplyTemplates(DOMElement $node, DOMNode $context, DOMNode $newContext, $first = false)
    {
        // Select the candidates to be processed
        $applyTemplatesSelect = $node->getAttribute('select');
        $applyTemplatesSelectParsed = $this->parseXPath($applyTemplatesSelect);

        $nodesMatched = $applyTemplatesSelectParsed->query($context);

        $fbPossibleTemplate = null;

        if (!$nodesMatched->length) {
            return;
        }

        $params = $this->getParamsFromXslWithParam($node, $context);
        $executed = false;

        foreach ($nodesMatched as $nodeMatched) {
            // Select a template that match
            foreach ($this->getGlobalContext()->getTemplates() as $template) {
                if (!$fbPossibleTemplate) {
                    $fbPossibleTemplate = $template;
                }

                $xPath = $template->getMatch();

                if (!$xPath) {
                    continue;
                }

                // Check that the mode matches
                $mode = $template->getMode();

                if (
                    ($node->hasAttribute('mode') && $mode !== $node->getAttribute('mode'))
                    || (!$node->hasAttribute('mode') && $mode != null)
                ) {
                    continue;
                }

                $xPathParsed = $this->parseXPath($xPath);
                $results = $xPathParsed->query(!$nodesMatched->item(0) instanceof \DOMDocument ? $nodeMatched->parentNode : $nodesMatched);

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
                    if ($possible->isSameNode($nodeMatched)) {
                        $isMatch = true;
                        break;
                    }
                }

                if ($isMatch) {
                    $this->getDebug()->showTemplate($template);
                    $this->getTemplateContextStack()->pushAClone();
                    $this->getTemplateContextStack()->top()->setContextParent($nodesMatched);
                    $this->processTemplate($template, $nodeMatched, $newContext, $params);
                    $this->getTemplateContextStack()->pop();

                    $executed = true;

                    break;
                }
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
            $this->getDebug()->showTemplate($template);
            $this->getTemplateContextStack()->pushAClone();
            $this->getTemplateContextStack()->top()->setContextParent($nodes);
            $this->processTemplate($fbPossibleTemplate, $contextNode, $newContext, $params);
            $this->getTemplateContextStack()->pop();
        }
    }

    protected function xslIf(DOMElement $node, DOMNode $context, DOMNode $newContext)
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

    protected function parseXPath($xPath)
    {
        $xPathParsed = null;
        $key = sha1($xPath);

        // If cache defined, try to get it from there
        if ($this->getCache()) {
            $cacheItem = $this->getCache()->getItem($key);

            if ($cacheItem->isHit()) {
                $xPathParsed = $cacheItem->get();
            }
        }

        // If no match from cache, parse the xPath
        if (!$xPathParsed) {
            $factory = new Factory();
            $xPathParsed = $factory->create($xPath);
        }

        // If was not in cache, save it if the cache is available
        if ($this->getCache() && !$cacheItem->isHit()) {
            $cacheItem->set($xPathParsed);
            $this->getCache()->save($cacheItem);
        }

        // Set the properties the xPath need for working
        $xPathParsed->setGlobalContext($this->getGlobalContext());
        $xPathParsed->setTemplateContext($this->getTemplateContextStack()->top());
        $transformed = $xPathParsed->toString();

        // FIXME: Move somewhere else
        if ($this->logXPath) {
            file_put_contents('xpath_log.txt', $xPath . ' =====> ' . $transformed . "\n", FILE_APPEND);
        }

        return $xPathParsed;
    }

    protected function evaluateAttrValueTemplates($attrValue, $context)
    {
        $factory = new Factory();
        $xPathParsed = $factory->createFromAttributeValue($attrValue);
        $xPathParsed->setGlobalContext($this->getGlobalContext());
        $xPathParsed->setTemplateContext($this->getTemplateContextStack()->top());

        return $xPathParsed->evaluate($context);
    }

    protected function xslValueOf(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // Evaluate the value
        $toEvaluateXPath = $node->getAttribute('select');
        $toEvaluate = $this->parseXPath($toEvaluateXPath);
        $result = $toEvaluate->evaluate($context);

        $this->getDebug()->showVar('result', $result);

        if ($result === true) {
            $result = 'true';
        } elseif ($result === false) {
            $result = 'false';
        }

        $domElementUtils = new DOMElementUtils();
        $wNode = $domElementUtils->getWritableNodeIn($newContext, $this->getOutput()->getCdataSectionElements());

        if ($result instanceof OriginalDOMNodeList || $result instanceof DOMNodeList) {
            foreach ($result as $subResult) {
                $wNode->nodeValue .= $subResult->nodeValue;
            }
        } else {
            $wNode->nodeValue .= $result;
        }
    }

    protected function xslText(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // Get the text
        $text = '';

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMText) {
                $text .= $childNode->nodeValue;
            }
        }

        $adoe = $node->getAttribute('disable-output-escaping');

        $domElementUtils = new DOMElementUtils();
        $wNode = $domElementUtils->getWritableNodeIn($newContext, $this->getOutput()->getCdataSectionElements());

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

    protected function xslCopyOf(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $selectXPath = $node->getAttribute('select');
        $selectParsed = $this->parseXPath($selectXPath);

        $results = $selectParsed->evaluate($context);

        if ($results instanceof OriginalDOMNodeList || $results instanceof DOMNodeList) {
            foreach ($results as $result) {
                $childNode = $this->newXml->importNode($result, true);
                $newContext->appendChild($childNode);
            }
        } else {
            $domElementUtils = new DOMElementUtils();
            $wNode = $domElementUtils->getWritableNodeIn($newContext, $this->getOutput()->getCdataSectionElements());
            $wNode->nodeValue .= $results;
        }
    }

    protected function xslCopy(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        if ($newContext instanceof DOMDocument) {
            $doc = $newContext;
        } else {
            $doc = $newContext->ownerDocument;
        }

        if ($context instanceof DOMText) {
            $childNode = $doc->createTextNode('');
            $childNode->nodeValue = $context->nodeValue;
        } elseif ($context instanceof DOMElement) {
            $childNode = $doc->importNode($context);
        } elseif ($context instanceof \DOMAttr) {
            $newContext->setAttribute($context->nodeName, $context->nodeValue);

            return;
        } else {
            throw new \RuntimeException('Class ' . get_class($context) . ' not supported in xsl:copy');
        }

        foreach ($this->getGlobalContext()->getNamespaces() as $prefix => $namespace) {
            if ($prefix === 'default' || $prefix == 'xsl' || $prefix == 'xml') {
                continue;
            }

            $newContext->setAttribute('xmlns:' . $prefix, $namespace);
        }

//        if ($childNode instanceof DOMElement) {
//            $utils = new DOMElementUtils();
//            $utils->removeAllAttributes($childNode);
//            $utils->removeAllChildren($childNode);
//        }
        $childNode = $newContext->appendChild($childNode);

        $this->processChildNodes($node, $context, $childNode);
    }

    protected function xslVariable(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');

        if (in_array($name, $this->getTemplateContextStack()->top()->getVariablesDeclaredInContext()->getArrayCopy())) {
            throw new \RuntimeException('Variables cannot be redeclared');
        }

        $this->getTemplateContextStack()->pushAClone();

        if ($node->hasAttribute('select')) {
            $selectXPath = $node->getAttribute('select');
            $selectXPathParsed = $this->parseXPath($selectXPath);
            $results = $selectXPathParsed->evaluate($context);
            $value = $results;
        } else {
            $value = $this->evaluateBody($node, $context, $newContext);
        }

        $this->getTemplateContextStack()->pop();

        $this->getTemplateContextStack()->top()->getVariables()[$name] = $value;
        $this->getTemplateContextStack()->top()->getVariablesDeclaredInContext()->append($name);

        $this->getDebug()->showVar($name, $this->getTemplateContextStack()->top()->getVariables()[$name]);
    }

    protected function xslChoose(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        foreach ($node->childNodes as $childNode) {
            // Ignore spaces
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            $this->getDebug()->startNodeLevel($this->newXml, $childNode);

            if ($childNode->nodeName === 'xsl:when') {
                if ($this->xslIf($childNode, $context, $newContext)) {
                    $this->getDebug()->endNodeLevel($this->newXml);
                    break;
                }

                $this->getDebug()->endNodeLevel($this->newXml);
            }

            if ($childNode->nodeName === 'xsl:otherwise') {
                $this->processChildNodes($childNode, $context, $newContext);
                $this->getDebug()->endNodeLevel($this->newXml);
            }
        }
    }

    protected function xslImport(DOMElement $node, DOMNode $context, DOMNode $newContext)
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

    protected function xslInclude(DOMElement $node, DOMNode $context, DOMNode $newContext)
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
        $tmpContext = $this->xml->createElement('tmptmptmptmptmpevaluateBody' . mt_rand(0, 9999999));

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

        return null;
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

            if ($childNode->nodeName !== 'xsl:sort') {
                continue;
            }

            $this->getDebug()->startNodeLevel($this->newXml, $childNode);

            if ($childNode->hasAttribute('select')) {
                $xPath = $childNode->getAttribute('select');
                $xPathParsed = $this->parseXPath($xPath);

                $newResults = $result->toArray();

                usort($newResults, function ($a, $b) use ($xPathParsed) {
                    return strcmp(
                        $xPathParsed->evaluate($a)->item(0)->nodeValue,
                        $xPathParsed->evaluate($b)->item(0)->nodeValue
                    );
                });

                $result = new DOMNodeList();
                $result->setSortable(false);
                $result->fromArray($newResults);
            }

            $this->getDebug()->endNodeLevel($this->newXml);

            break;
        }

        foreach ($result as $eachNode) {
            $this->getTemplateContextStack()->pushAClone();
            $this->getTemplateContextStack()->top()->setContextParent($result);
            $this->processChildNodes($node, $eachNode, $newContext);
            $this->getTemplateContextStack()->pop();
        }
    }

    protected function xslForEachGroup(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $xPath = $node->getAttribute('select');
        $xPathParsed = $this->parseXPath($xPath);
        $result = $xPathParsed->evaluate($context);

        if (!$result->length) {
            return;
        }

        $groups = [];
        $criteria = null;
        $lastMatchedCriteria = null;
        $currentGroup = new DOMNodeList();
        $currentGroup->setSortable(false);

        foreach ($result as $resultSingle) {
            if ($node->hasAttribute('group-adjacent')) {
                if (!$criteria) {
                    $criteria = $node->getAttribute('group-adjacent');
                    $criteria = $this->parseXPath($criteria);
                }

                $criteriaExec = $criteria->evaluate($resultSingle);

                if ($criteriaExec !== $lastMatchedCriteria && $lastMatchedCriteria !== null) {
                    //                    if (count($currentGroup) > 1) {
                        $groups[$lastMatchedCriteria] = $currentGroup;
//                    }

                    $currentGroup = new DOMNodeList();
                    $currentGroup->setSortable(false);
                }

                $currentGroup[] = $resultSingle;

                $lastMatchedCriteria = $criteriaExec;
            } else {
                throw new \RuntimeException('Criteria for xsl:for-each-group not implemented');
            }
        }

        $groups[$lastMatchedCriteria] = $currentGroup;

        foreach ($groups as $groupName => $group) {
            $this->getTemplateContextStack()->pushAClone();
            $this->getTemplateContextStack()->top()->setGroupingKey($groupName);
            $this->getTemplateContextStack()->top()->setGroup($group);
            $this->processChildNodes($node, $group->item(0), $newContext);
            $this->getTemplateContextStack()->pop();
        }
    }

    protected function xslFunction(DOMElement $node, DOMNode $context, DOMNode $newContext)
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

    protected function xslElement(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $namespace = $node->getAttribute('namespace');
        $name = $node->getAttribute('name');

        $namespace = $this->evaluateAttrValueTemplates($namespace, $context);
        $name = $this->evaluateAttrValueTemplates($name, $context);

        $document = $newContext;

        if (!$document instanceof DOMDocument) {
            $document = $document->ownerDocument;
        }

        if ($namespace) {
            $newNode = $document->createElementNS($namespace, $name);
        } else {
            $newNode = $document->createElement($name);
        }

        $newNode = $newContext->appendChild($newNode);

        $this->processChildNodes($node, $context, $newNode);
    }

    protected function xslSequence(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $this->xslCopyOf($node, $context, $newContext);
    }

    protected function xslAnalyzeString(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $regex = '#' . str_replace('#', '\#', $node->getAttribute('regex')) . '#';

        // Get the nodes to process
        $xPath = $node->getAttribute('select');
        $result = $this->parseXPath($xPath)->evaluate($context);

        // Get the nodes of the actions to apply
        $matchingNode = null;
        $nonMatchingNode = null;

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->nodeName === 'xsl:matching-substring') {
                $matchingNode = $childNode;
            } elseif ($childNode instanceof DOMElement && $childNode->nodeName === 'xsl:non-matching-substring') {
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

    protected function xslMatchingOrNotMatchingSubstring(DOMElement $node, $match, DOMNode $newContext)
    {
        $tmpContext = $this->xml->createElement('tmptmptmptmptmpmonmsubstring');
        $domElementUtils = new DOMElementUtils();
        $text = $domElementUtils->getWritableNodeIn($tmpContext, $this->getOutput()->getCdataSectionElements());
        $text->nodeValue = $match;

        $this->processChildNodes($node, $tmpContext, $newContext);
    }

    protected function xslDecimalFormat(DOMElement $node, DOMNode $context, DOMNode $newContext)
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

    protected function xslKey(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $key = new Key();
        $key->setMatch($node->getAttribute('match'));
        $key->setUse($node->getAttribute('use'));

        $this->getGlobalContext()->getKeys()[$node->getAttribute('name')] = $key;
    }

    protected function xslComment(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $value = $this->evaluateBody($node, $context, $newContext);
        /* @var $doc DOMDocument */
        $doc = $newContext->ownerDocument ?: $newContext;

        $comment = $doc->createComment($value);
        $newContext->appendChild($comment);
    }

    protected function getParamsFromXslWithParam(DOMElement $node, DOMNode $context)
    {
        $params = [];

        // Detect possible params
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->nodeName !== 'xsl:with-param') {
                throw new RuntimeException('Found not recognized "' . $childNode->nodeName . '" inside xsl:call-template');
            }

            $this->getDebug()->startNodeLevel($this->newXml, $childNode);

            if ($childNode->hasAttribute('select')) {
                $xPath = $childNode->getAttribute('select');
                $xPathParsed = $this->parseXPath($xPath);
                $result = $xPathParsed->evaluate($context);

                $params[$childNode->getAttribute('name')] = $result;
            } else {
                $params[$childNode->getAttribute('name')] = $this->evaluateBody($childNode, $context);
            }

            $this->getDebug()->endNodeLevel($this->newXml);
        }

        return $params;
    }

    /**
     * xsl:call-template.
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function xslCallTemplate(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');
        $params = $this->getParamsFromXslWithParam($node, $context);

        // Select the candidates to be processed
        $templates = $this->getGlobalContext()->getTemplates()->getByName($name);

        if (!count($templates)) {
            throw new RuntimeException('No templates by the name "' . $name . '" found');
        }

        if (count($templates) > 1) {
            throw new RuntimeException('Multiple templates by the name "' . $name . '" found');
        }

        $this->getTemplateContextStack()->pushAClone();
        $this->processTemplate($templates[0], $context, $newContext, $params);
        $this->getTemplateContextStack()->pop();
    }

    protected function xslParam(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');

        if (
            !isset($this->getTemplateContextStack()->top()->getVariables()[$name])
            || !in_array($name, $this->getTemplateContextStack()->top()->getVariablesDeclaredInContext()->getArrayCopy())
        ) {
            if ($node->hasAttribute('select')) {
                $select = $node->getAttribute('select');
                $selectParsed = $this->parseXPath($select);
                $result = $selectParsed->evaluate($context);

                $this->getTemplateContextStack()->top()->getVariables()[$name] = $result;
            } elseif ($node->childNodes->length > 0) {
                $this->getTemplateContextStack()->top()->getVariables()[$name] = $this->evaluateBody($node, $context, $newContext);
//            } else {
//                $value = $this->evaluateBody($node, $context, $newContext);
//                $this->getTemplateContextStack()->top()->getVariables()[$name] = $value;
            } else {
                $this->getTemplateContextStack()->top()->getVariables()[$name] = null;
            }

            $this->getTemplateContextStack()->top()->getVariablesDeclaredInContext()->append($name);
        }
    }

    protected function xslSort(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // xsl:sort is implemented inside the body of the functions that use it, so nothing to do here.
    }

    protected function xslStripSpace(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $elements = $node->getAttribute('elements');
        $elements = explode(' ', $elements);
        $newElements = [];

        foreach ($elements as $element) {
            $parts = explode(':', $element);

            if (count($parts) === 1) {
                $newElements[$this->getGlobalContext()->getNamespaces()[$this->getGlobalContext()->getDefaultNamespace()]][$parts[0]] = $parts[0];
            } else {
                $newElements[$this->getGlobalContext()->getNamespaces()[$parts[0]]][$parts[1]] = $parts[1];
            }
        }

        $xPathLeaves = $this->parseXPath('//*[not(*)]');
        $leaves = $xPathLeaves->evaluate($context);

        foreach ($leaves as $leaf) {
            /** @var $leaf DOMElement */
            if (isset($newElements[$leaf->namespaceURI][$leaf->localName]) && preg_match('#^\s*$#', $leaf->nodeValue)) {
                $leaf->nodeValue = '';
            }
        }
    }

    protected function xslMessage(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        if ($node->hasAttribute('terminate')) {
            $terminate = $node->getAttribute('terminate');
        } else {
            $terminate = 'no';
        }

        $message = $this->evaluateBody($node, $context, $newContext);

        $this->messages[] = $message;

        if ($terminate === 'yes') {
            throw new MessageTerminatedException();
        }
    }

    protected function xslResultDocument(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        trigger_error('xsl:result-document not supported yet');
    }

    protected function xslProcessingInstruction(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');
        $content = $this->evaluateBody($node, $context);

        $doc = ($context instanceof DOMDocument ? $context : $context->ownerDocument);

        $doc->appendChild($doc->createProcessingInstruction($name, $content));
    }
}
