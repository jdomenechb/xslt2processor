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
use Jdomenechb\XSLT2Processor\XML\DOMResultTree;
use Jdomenechb\XSLT2Processor\XPath\Expression\Converter;
use Jdomenechb\XSLT2Processor\XPath\ExpressionInterface;
use Jdomenechb\XSLT2Processor\XPath\Factory;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;
use Jdomenechb\XSLT2Processor\XPath\XPathPath;
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
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $decimalFormats = [];

    /**
     * Determines if the the template being processed right now is imported or not.
     *
     * @var bool
     */
    protected $isImported = false;

    /**
     * Determines if the the template being processed right now is included or not.
     *
     * @var bool
     */
    protected $isIncluded = false;

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
     * @var Factory
     */
    protected $xPathFactory;

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
        $this->xPathFactory = new Factory();
    }

    /**
     * Main function to be called to transform the source XML with the XSL stylesheet defined.
     *
     * @throws \RuntimeException
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

            if ($errno === E_USER_WARNING || $errno === E_USER_NOTICE) {
                return false;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        // Set the basic things needed
        $this->globalContext = new GlobalContext();
        $this->newXml = new DOMDocument();
        $this->messages = new \ArrayObject();

        $this->debug->setOutput($this->getGlobalContext()->getOutputs()['']);

        // Process the base xslStylesheet node to be aware of everything the XSL stylesheet features
        $this->xslStylesheet($this->stylesheet->documentElement, $this->xml, $this->newXml);

        // Create a dummy template to be executed to start all the process
        $node = $this->stylesheet->createElement('init-template');
        $node->setAttribute('select', '/');

        try {
            $this->xslApplyTemplates($node, $this->xml, $this->newXml);
        } catch (MessageTerminatedException $ex) {
            trigger_error('Template execution was terminated because of an xsl:message');
        }

        // Restore back the error handler we had
        restore_error_handler();

        // Cleanup the factory cache
        Factory::cleanXPathCache();

        // Return the result according to the output parameters
        $output = $this->getGlobalContext()->getOutputs()[''];

        return $output->formatXml($this->newXml);
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

    /**
     * xsl:stylesheet.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     */
    protected function xslStylesheet(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $this->debug->startNodeLevel($this->newXml, $node);

        // Add to the stylesheet stack the current XSLT stylesheet
        $this->globalContext->getStylesheetStack()->push($node->ownerDocument);

        $this->getGlobalContext()->getNamespaces()[GlobalContext::NAMESPACE_XSL] = $node->namespaceURI;

        foreach ($node->attributes as $attribute) {
            // Default namespace
            if ($attribute->nodeName === 'xpath-default-namespace') {
                $this->getGlobalContext()->getNamespaces()[$this->getGlobalContext()->getDefaultNamespace()] =
                    $attribute->nodeValue;

                continue;
            }

            // XSL version
            if ($attribute->nodeName === 'version') {
                $this->version = $attribute->nodeValue;
                continue;
            }

            // Extension element prefixes
            if ($attribute->nodeName === 'extension-element-prefixes') {
                $eep = explode(' ', $attribute->nodeValue);

                foreach ($eep as $eepElement) {
                    $this->getGlobalContext()->getExtensionElementPrefixes()->offsetSet($eepElement, $eepElement);
                }

                continue;
            }

//            trigger_error(
//                'xsl:stylesheet attribute "' . $attribute->nodeName . '" not implemented',
//                E_USER_WARNING
//            );
        }

        $xpath = new DOMXPath($node->ownerDocument);

        foreach ($xpath->query('namespace::*', $node) as $nsNode) {
            $this->getGlobalContext()->getNamespaces()[$nsNode->localName] = $nsNode->namespaceURI;
        }

        // Start processing the template
        $this->processChildNodes($node, $context, $newContext);

        // Remove the stylesheet from the stack
        $this->globalContext->getStylesheetStack()->pop();

        $this->debug->endNodeLevel($this->newXml);
    }

    protected function processChildNodes(DOMNode $parent, DOMNode $context, DOMNode $newContext)
    {
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
                $methodName = 'xsl' . str_replace('-', '', ucwords($childNode->localName, '-'));

                $this->debug->startNodeLevel($this->newXml, $childNode);

                if (method_exists($this, $methodName)) {
                    $this->$methodName($childNode, $context, $newContext);
                } else {
                    throw new RuntimeException('The XSL tag ' . $childNode->nodeName . ' is not supported yet');
                }

                $this->debug->endNodeLevel($this->newXml);

                continue;
            }

            if ($childNode instanceof DOMText) {
                $domElementUtils = new DOMElementUtils();
                $domElementUtils->appendTextTo(
                    $newContext,
                    $childNode->nodeValue,
                    $this->getGlobalContext()->getOutputs()['']->getCdataSectionElements()
                );

                continue;
            }

            $this->processNormalNode($childNode, $context, $newContext);
        }
    }

    /**
     * Processes a node that is not an XSL transformation node.
     *
     * @param DOMNode $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function processNormalNode(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $this->debug->startNodeLevel($this->newXml, $node);

        if ($this->getTemplateContextStack()->count() !== 1) {
            // Copy the node to the new document
            $doc = $newContext->ownerDocument ?: $newContext;
            $newNode = $doc->importNode($node);

            $nodesToDelete = [];

            /** @var DOMElement $newNode */
            $newNode = $newContext->appendChild($newNode);

            foreach ($newNode->childNodes as $child) {
                $nodesToDelete[] = $child;
            }

            foreach ($nodesToDelete as $child) {
                $newNode->removeChild($child);
            }

            // Process attributes
            foreach ($newNode->attributes as $attribute) {
                $newNode->setAttribute(
                    $attribute->nodeName,
                    $this->evaluateAttrValueTemplates($attribute->nodeValue, $context)
                );
            }

            $this->processChildNodes($node, $context, $newNode);
        } else {
            $this->debug->printText('Node ignored due to being executed in xsl:stylesheet');
        }

        $this->debug->endNodeLevel($this->newXml);
    }

    protected function processXsltNode(DOMNode $node, DOMNode $context, DOMNode $newContext)
    {
        $methodName = 'xsl' . str_replace('-', '', ucwords($node->localName, '-'));

        $this->debug->startNodeLevel($this->newXml, $node);

        if (method_exists($this, $methodName)) {
            $this->$methodName($node, $context, $newContext);
        } else {
            throw new RuntimeException('The XSL tag  ' . $node->nodeName . ' is not supported yet');
        }

        $this->debug->endNodeLevel($this->newXml);
    }

    /**
     * xsl:output.
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function xslOutput(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        if (!$node->hasAttribute('name')) {
            $name = '';
        } else {
            $name = $node->getAttribute('name');
        }

        if (!$this->getGlobalContext()->getOutputs()->offsetExists($name)) {
            $output = new Output();
            $this->getGlobalContext()->getOutputs()[$name] = $output;
        } else {
            $output = $this->getGlobalContext()->getOutputs()[$name];
        }

        foreach ($node->attributes as $attribute) {
            switch ($attribute->nodeName) {
                case 'omit-xml-declaration':
                    $output->setRemoveXmlDeclaration($attribute->nodeValue === 'yes');
                    break;

                case 'indent':
                    $this->newXml->formatOutput = $attribute->nodeValue === 'yes';
                    $this->newXml->preserveWhiteSpace = $attribute->nodeValue !== 'yes';
                    break;

                case 'method':
                    $output->setMethod($attribute->nodeValue);
                    break;

                case 'encoding':
                    $this->newXml->encoding = $attribute->nodeValue;
                    break;

                case 'cdata-section-elements':
                    $elements = explode(' ', $attribute->nodeValue);
                    $output->setCdataSectionElements(array_merge(
                        $output->getCdataSectionElements(),
                        $elements
                    ));

                    break;

                case 'version':
                    $output->setVersion($attribute->nodeValue);
                    break;

                case 'doctype-public':
                    $output->setDoctypePublicAttribute($attribute->nodeValue);
                    break;

                case 'doctype-system':
                    $output->setDoctypeSystemAttribute($attribute->nodeValue);
                    break;

                case 'name':
                    // Name already treated
                    break;

                default:
                    trigger_error($attribute->nodeName . ' attribute from xslOutput not supported', E_USER_WARNING);
            }
        }
    }

    /**
     * xsl:template.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     *
     * @throws RuntimeException
     */
    protected function xslTemplate(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        if (
            $this->isIncluded
            && $node->hasAttribute('name')
            && $this->getGlobalContext()->getTemplates()->getByName($name = $node->getAttribute('name'))->count()
        ) {
            throw new \RuntimeException(
                'Template "' . $name . '" cannot be included: a template with the same name already exists'
            );
        }

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

        if (!$this->isImported && !$this->isIncluded) {
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

    /**
     * xsl:apply-templates.
     *
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     * @param bool $first
     * @return bool
     */
    protected function xslApplyTemplates(DOMElement $node, DOMNode $context, DOMNode $newContext, $first = false)
    {
        // Select the candidates to be processed
        if (!$applyTemplatesSelect = $node->getAttribute('select')) {
            $applyTemplatesSelect = 'node()';
        }

        // Get the candidates
        $applyTemplatesSelectParsed = $this->parseXPath($applyTemplatesSelect);
        $nodesMatched = $applyTemplatesSelectParsed->query($context);
        $nodesMatched = $this->sortElementsByXslSort($node, $nodesMatched);

        $fbPossibleTemplate = null;

        if (!$nodesMatched->length) {
            return false;
        }

        $params = $this->getParamsFromXslWithParam($node, $context);

        foreach ($nodesMatched as $nodeMatched) {
            $isMatch = false;

            // Select a template that match
            foreach ($this->getGlobalContext()->getTemplates()->getArrayCopy() as $template) {
                /* @var Template $template */
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
                    (!$node->hasAttribute('mode') && $mode !== null)
                    || ($node->hasAttribute('mode') && $mode !== $node->getAttribute('mode'))
                ) {
                    continue;
                }

                $xPathParsed = $this->parseXPath($xPath);

                // By default, use as context:
                // - The document itself if the instance is a DOMDocument
                // - The parent node if the instance is not a DOMDocument
                // - A previous parent if there are more levels in the match

                $matchContext = $nodeMatched;

                if (!$nodeMatched instanceof \DOMDocument) {
                    $matchContext = $nodeMatched->parentNode;

                    if ($xPathParsed instanceof XPathPath) {
                        for ($i = 0; $i < count($xPathParsed->getParts()) - 1 && $matchContext->parentNode !== null; $i++) {
                            $matchContext = $matchContext->parentNode;
                        }
                    }
                }

                $results = $xPathParsed->query($matchContext);

                if ($results === false) {
                    continue;
                }

                if (!$results instanceof OriginalDOMNodeList && !$results instanceof DOMNodeList) {
                    throw new RuntimeException(
                        'xPath "' . $template->getMatch() . '" evaluation wrong: expected DOMNodeList'
                    );
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
                    $this->debug->showTemplate($template);

                    $this->getGlobalContext()->getStylesheetStack()->push($template->getNode()->ownerDocument);
                    $this->getTemplateContextStack()->pushAClone();

                    $this->getTemplateContextStack()->top()->setContextParent($nodesMatched);
                    $this->processTemplate($template, $nodeMatched, $newContext, $params);

                    $this->getTemplateContextStack()->pop();
                    $this->getGlobalContext()->getStylesheetStack()->pop();

                    break;
                }
            }

            if (!$isMatch) {
                // Execute the default behaviour
                if ($nodeMatched instanceof \DOMCharacterData && !$nodeMatched instanceof DOMComment) {
                    $this->debug->printText('Template: default behaviour TEXT');

                    $domElementUtils = new DOMElementUtils();
                    $domElementUtils->appendTextTo(
                        $newContext,
                        $nodeMatched->nodeValue,
                        $this->getGlobalContext()->getOutputs()['']->getCdataSectionElements()
                    );
                } elseif ($nodeMatched instanceof DOMElement || $nodeMatched instanceof DOMDocument) {
                    $this->debug->printText('Template: default behaviour NODE');

                    // Create a temporal apply-templates node
                    $tmpApplyTemplates = $this->stylesheet->createElement('apply-templates');
                    // TODO: Add parameters

                    $this->debug->startNodeLevel($this->newXml, $tmpApplyTemplates);
                    $this->xslApplyTemplates($tmpApplyTemplates, $nodeMatched, $newContext, false);
                    $this->debug->endNodeLevel($this->newXml);
                }
            }
        }
    }

    /**
     * xsl:if.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     *
     * @return bool
     */
    protected function xslIf(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // Evaluate the if
        $toEvaluate = $node->getAttribute('test');
        $xPathParsed = $this->parseXPath($toEvaluate);
        $result = $xPathParsed->evaluate($context);

        $this->debug->printText('Evaluated condition');
        $this->debug->show($result);

        if (
            $result === true
            || ((is_string($result) || is_float($result) || is_int($result)) && $result)
            || ($result instanceof OriginalDOMNodeList && $result->length)
            || ($result instanceof DOMNodeList && $result->count())
            || ($result instanceof DOMResultTree && $result->getBaseNode())
        ) {
            $this->processChildNodes($node, $context, $newContext);

            return true;
        }

        return false;
    }

    /**
     * Given a string xPath, returns a chain of ExpressionInterface objects representing the XPath.
     *
     * @param string $xPath
     *
     * @return ExpressionInterface
     */
    protected function parseXPath($xPath)
    {
        $xPathParsed = null;
        $hasCache = (bool) $this->cache;

        // If cache defined, try to get it from there
        if ($hasCache) {
            $cacheItem = $this->getCache()->getItem(sha1($xPath));
            $isHit = $cacheItem->isHit();

            if ($isHit) {
                $xPathParsed = $cacheItem->get();
            }
        }

        // If no match from cache, parse the xPath
        if ($xPathParsed === null) {
            $xPathParsed = $this->xPathFactory->create($xPath);
        }

        // If was not in cache, save it if the cache is available
        if ($hasCache && !$isHit) {
            $cacheItem->set($xPathParsed);
            $this->getCache()->save($cacheItem);
        }

        // Set the properties the xPath need for working
        $xPathParsed->setGlobalContext($this->getGlobalContext());
        $xPathParsed->setTemplateContext($this->getTemplateContextStack()->top());

        return $xPathParsed;
    }

    /**
     * Creates and evaluates an attribute template, giving the value of the attribute of the node.
     * @param $attrValue
     * @param $context
     * @return mixed|string
     */
    protected function evaluateAttrValueTemplates($attrValue, $context)
    {
        $xPathParsed = $this->xPathFactory->createFromAttributeValue($attrValue);
        $xPathParsed->setGlobalContext($this->getGlobalContext());
        $xPathParsed->setTemplateContext($this->getTemplateContextStack()->top());

        return $xPathParsed->evaluate($context);
    }

    /**
     * xsl:value-of.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     */
    protected function xslValueOf(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // Evaluate the value
        $toEvaluateXPath = $node->getAttribute('select');
        $toEvaluate = $this->parseXPath($toEvaluateXPath);
        $result = $toEvaluate->evaluate($context);

        $this->debug->showVar('result', $result);

        $domElementUtils = new DOMElementUtils();
        $cDataSectionElements = $this->getGlobalContext()->getOutputs()['']->getCdataSectionElements();

        if ($result instanceof OriginalDOMNodeList || $result instanceof DOMNodeList) {
            foreach ($result as $subResult) {
                $domElementUtils->appendTextTo($newContext, $subResult->nodeValue, $cDataSectionElements);
            }
        } elseif ($result instanceof DOMResultTree) {
            $domElementUtils->appendTextTo($newContext, $result->evaluate(), $cDataSectionElements);
        } else {
            $domElementUtils->appendTextTo($newContext, $result, $cDataSectionElements);
        }
    }

    /**
     * xsl:text.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     */
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
        $wNode = $domElementUtils->getWritableNodeIn($newContext, $this->getGlobalContext()->getOutputs()['']->getCdataSectionElements());

        if ($adoe !== 'yes') {
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

        $this->debug->show($text);
    }

    /**
     * xsl:copy-of.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     */
    protected function xslCopyOf(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $selectXPath = $node->getAttribute('select');
        $selectParsed = $this->parseXPath($selectXPath);

        $results = $selectParsed->evaluate($context);

        $this->debug->printText('Before copy:');
        $this->debug->show($results);

        if ($results instanceof DOMResultTree) {
            $results = $results->evaluate();
        }

        if ($results instanceof OriginalDOMNodeList || $results instanceof DOMNodeList) {
            foreach ($results as $result) {
                $childNode = $this->newXml->importNode($result, true);
                $newContext->appendChild($childNode);
            }
        } else {
            $domElementUtils = new DOMElementUtils();
            $domElementUtils->appendTextTo(
                $newContext,
                $results,
                $this->getGlobalContext()->getOutputs()['']->getCdataSectionElements()
            );
        }

        $this->debug->printText('After copy:');
        $this->debug->show($results);
    }

    /**
     * xsl:copy.
     *
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function xslCopy(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $doc = Converter::fromDOMToDOMDocument($newContext);

        if ($context instanceof DOMText) {
            $childNode = $doc->createTextNode('');
            $childNode->nodeValue = $context->nodeValue;
        } elseif ($context instanceof DOMElement) {
            /** @var DOMElement $childNode */
            $childNode = $doc->importNode($context);

            // Cleanup attributes
            if ($context->attributes) {
                foreach ($context->attributes as $attribute) {
                    $childNode->removeAttribute($attribute->nodeName);
                }
            }

            // Attribute-sets
            if ($node->hasAttribute('use-attribute-sets')) {
                $attrSets = explode(' ', $node->getAttribute('use-attribute-sets'));
                $defAttrSets = $this->getGlobalContext()->getAttributeSets();

                foreach ($attrSets as $attrSet) {
                    if (!$defAttrSets->offsetExists($attrSet)) {
                        throw new \RuntimeException('Attribute set "' . $attrSet . '" not defined');
                    }

                    foreach ($defAttrSets->offsetGet($attrSet) as $attrName => $attrValue) {
                        $childNode->setAttribute($attrName, $attrValue);
                    }
                }
            }
        } elseif ($context instanceof \DOMAttr) {
            $newContext->setAttribute($context->nodeName, $context->nodeValue);

            return;
        } elseif ($context instanceof DOMComment) {
            // DOMComments should be ignored

            return;
        } else {
            throw new \RuntimeException('Class ' . get_class($context) . ' not supported in xsl:copy');
        }

        $childNode = $newContext->appendChild($childNode);
        $toAddPrefix = $newContext instanceof DOMDocument ? $newContext->documentElement : $newContext;

        foreach ($this->getGlobalContext()->getNamespaces() as $prefix => $namespace) {
            if ($prefix === 'default' || $prefix === 'xsl' || $prefix === 'xml' || $prefix === 'fn') {
                continue;
            }

            $toAddPrefix->setAttribute('xmlns:' . $prefix, $namespace);
        }

        $this->processChildNodes($node, $context, $childNode);
    }

    /**
     * xsl:variable.
     *
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function xslVariable(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');

        if (
            in_array(
                $name,
                $this->getTemplateContextStack()->top()->getVariablesDeclaredInContext()->getArrayCopy(),
                true
            )
        ) {
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

        $this->debug->showVar($name, $this->getTemplateContextStack()->top()->getVariables()[$name]);
    }

    protected function xslChoose(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        foreach ($node->childNodes as $childNode) {
            // Ignore spaces
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            $this->debug->startNodeLevel($this->newXml, $childNode);

            if ($childNode->nodeName === 'xsl:when') {
                if ($this->xslIf($childNode, $context, $newContext)) {
                    $this->debug->endNodeLevel($this->newXml);
                    break;
                }

                $this->debug->endNodeLevel($this->newXml);
            }

            if ($childNode->nodeName === 'xsl:otherwise') {
                $this->processChildNodes($childNode, $context, $newContext);
                $this->debug->endNodeLevel($this->newXml);
            }
        }
    }

    /**
     * xsl:import.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     */
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
        $wasIncluded = $this->isIncluded;

        $this->isImported = true;
        $this->isIncluded = false;

        $this->xslStylesheet($importedXslt->documentElement, $context, $newContext);

        $this->isImported = $wasImported;
        $this->isIncluded = $wasIncluded;

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

        $includedXslt = new DOMDocument();
        $includedXslt->load($href);

        $wasImported = $this->isImported;
        $wasIncluded = $this->isIncluded;

        $this->isImported = false;
        $this->isIncluded = true;

        $this->xslStylesheet($includedXslt->documentElement, $context, $newContext);

        $this->isImported = $wasImported;
        $this->isIncluded = $wasIncluded;

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
            $value = $this->evaluateBody($node, $context, $newContext)->evaluate();
        }

        $newContext->setAttribute($name, $value);
    }

    protected function evaluateBody(DOMElement $node, DOMNode $context, DOMNode $newContext = null)
    {
        // Create temporal context
        $tmpContext = new DOMResultTree($this->xml);

        $this->processChildNodes($node, $context, $tmpContext->getBaseNode());

        return $tmpContext;
    }

    /**
     * xsl:for-each.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     *
     * @throws RuntimeException
     */
    protected function xslForEach(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $xPath = $node->getAttribute('select');
        $xPathParsed = $this->parseXPath($xPath);
        $result = $xPathParsed->evaluate($context);

        if (!$result->length) {
            return;
        }

        $result = $this->sortElementsByXslSort($node, $result);

        foreach ($result as $eachNode) {
            $this->getTemplateContextStack()->pushAClone();
            $this->getTemplateContextStack()->top()->setContextParent($result);
            $this->processChildNodes($node, $eachNode, $newContext);
            $this->getTemplateContextStack()->pop();
        }
    }

    /**
     * xsl:for-each-group
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
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

        foreach ($result as $resultSingle) {
            if ($node->hasAttribute('group-adjacent')) {
                if (!$criteria) {
                    $criteria = $node->getAttribute('group-adjacent');
                    $criteria = $this->parseXPath($criteria);
                }

                // Get the criteria values to ues
                $criteriaExec = $criteria->evaluate($resultSingle);

                // If the criteria is not the same as the previous one, change group
                if ($criteriaExec !== $lastMatchedCriteria && $lastMatchedCriteria !== null) {
                    $groups[$lastMatchedCriteria] = $currentGroup;
                    $currentGroup = [];
                }

                // Add the item
                $currentGroup[] = $resultSingle;
                $lastMatchedCriteria = $criteriaExec;
            } elseif ($node->hasAttribute('group-by')) {
                if (!$criteria) {
                    $criteria = $node->getAttribute('group-by');
                    $criteria = $this->parseXPath($criteria);
                }

                // Get the criteria values to ues
                $criteriaExec = $criteria->evaluate($resultSingle);

                if (!$criteriaExec instanceof DOMNodeList) {
                    $criteriaExec = [$criteriaExec];
                }

                foreach ($criteriaExec as $criteriaExecItem) {
                    $cEIString = Converter::fromDOMToString($criteriaExecItem);
                    $groups[$cEIString][] = $resultSingle;
                }
            } else {
                throw new \RuntimeException('Criteria for xsl:for-each-group not implemented');
            }
        }

        if ($node->hasAttribute('group-adjacent')) {
            $groups[$lastMatchedCriteria] = $currentGroup;
        }

        // Detect sortings
        // TODO
//        foreach ($node->childNodes as $childNode) {
//            if (!$childNode instanceof DOMElement || $childNode->nodeName !== 'xsl:sort') {
//                continue;
//            }
//
//            $this->debug->startNodeLevel($this->newXml, $childNode);
//
//            if ($childNode->hasAttribute('select')) {
//                $xPath = $childNode->getAttribute('select');
//                $xPathParsed = $this->parseXPath($xPath);
//
//                $newResults = $result->toArray();
//
//                usort($newResults, function ($a, $b) use ($xPathParsed) {
//                    return strcmp(
//                        $xPathParsed->evaluate($a)->item(0)->nodeValue,
//                        $xPathParsed->evaluate($b)->item(0)->nodeValue
//                    );
//                });
//
//                $result = new DOMNodeList();
//                $result->setSortable(false);
//                $result->fromArray($newResults);
//            }
//
//            $this->debug->endNodeLevel($this->newXml);
//
//            break;
//        }

        foreach ($groups as $groupName => $group) {
            $this->getTemplateContextStack()->pushAClone();
            $this->getTemplateContextStack()->top()->setGroupingKey($groupName);
            $this->getTemplateContextStack()->top()->setGroup(new DOMNodeList($group));
            $this->processChildNodes($node, $group[0], $newContext);
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
        $text = $domElementUtils->getWritableNodeIn($tmpContext, $this->getGlobalContext()->getOutputs()['']->getCdataSectionElements());
        $text->nodeValue = $match;

        $this->processChildNodes($node, $tmpContext, $newContext);
    }

    /**
     * xsl:decimal-format.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     *
     * @throws RuntimeException
     */
    protected function xslDecimalFormat(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = 'default';
        $info = [
            'decimal-separator' => '.',
            'grouping-separator' => ',',
            'infinity' => 'Infinity',
            'minus-sign' => '.-',
            'NaN' => 'NaN',
            'percent' => '%',
            'per-mille' => '‰',
            'zero-digit' => '0',
            'digit' => '#',
            'pattern-separator' => ';',
        ];

        foreach ($node->attributes as $attribute) {
            if ($attribute->nodeName !== 'name') {
                $info[$attribute->nodeName] = $attribute->nodeValue;
            } else {
                $name = $attribute->nodeValue;
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

    /**
     * xsl:comment.
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function xslComment(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $value = $this->evaluateBody($node, $context, $newContext)->evaluate();
        /* @var $doc DOMDocument */
        $doc = $newContext->ownerDocument ?: $newContext;

        $comment = $doc->createComment($value);

        $newContext->appendChild($comment);
    }

    /**
     * xsl:with-param. Returns the parameters defined inside the call to the node.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     *
     * @throws RuntimeException
     *
     * @return array
     */
    protected function getParamsFromXslWithParam(DOMElement $node, DOMNode $context)
    {
        $params = [];

        // Detect possible params
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->nodeName !== 'xsl:with-param' && $childNode->nodeName !== 'xsl:sort') {
                throw new RuntimeException(
                    'Found not recognized "' . $childNode->nodeName . '" inside ' . $node->nodeName
                );
            }

            $this->debug->startNodeLevel($this->newXml, $childNode);

            if ($childNode->hasAttribute('select')) {
                $xPath = $childNode->getAttribute('select');
                $xPathParsed = $this->parseXPath($xPath);
                $result = $xPathParsed->evaluate($context);

                $params[$childNode->getAttribute('name')] = $result;
            } else {
                $params[$childNode->getAttribute('name')] = $this->evaluateBody($childNode, $context);
            }

            $this->debug->printText('Result:');
            $this->debug->show($params[$childNode->getAttribute('name')]);

            $this->debug->endNodeLevel($this->newXml);
        }

        return $params;
    }

    /**
     * xsl:call-template.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
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

        $this->getGlobalContext()->getStylesheetStack()->push($templates[0]->getNode()->ownerDocument);
        $this->getTemplateContextStack()->pushAClone();
        $this->processTemplate($templates[0], $context, $newContext, $params);
        $this->getTemplateContextStack()->pop();
        $this->getGlobalContext()->getStylesheetStack()->pop();
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
                $this->getTemplateContextStack()->top()->getVariables()[$name] =
                    $this->evaluateBody($node, $context, $newContext)->evaluate();
            } else {
                $this->getTemplateContextStack()->top()->getVariables()[$name] = null;
            }

            $this->getTemplateContextStack()->top()->getVariablesDeclaredInContext()->append($name);

            $this->debug->printText('Result:');
            $this->debug->show($this->getTemplateContextStack()->top()->getVariables()[$name]);
        } else {
            $this->debug->printText('Parameter already defined');
        }
    }

    /**
     * Fake xsl:sort.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     * @param bool       $inside
     */
    protected function xslSort(DOMElement $node, DOMNode $context, DOMNode $newContext, $inside = false)
    {
        // xsl:sort is implemented inside the body of the functions that use it, so nothing to do here.
    }

    /**
     * xsl:sort. Sorts the given array by the criteria in the XSL node.
     *
     * @param DOMElement  $node
     * @param DOMNodeList $toSort
     *
     * @throws RuntimeException
     *
     * @return DOMNodeList
     */
    protected function sortElementsByXslSort(DOMElement $node, DOMNodeList $toSort)
    {
        // Detect sortings
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->nodeName !== 'xsl:sort') {
                continue;
            }

            $this->debug->startNodeLevel($this->newXml, $childNode);

            if ($childNode->hasAttribute('select')) {
                $xPath = $childNode->getAttribute('select');
                $xPathParsed = $this->parseXPath($xPath);

                $newResults = $toSort->toArray();

                $order = 1;

                if ($childNode->hasAttribute('order') && $childNode->getAttribute('order') === 'descending') {
                    $order = -1;
                }

                $dataType = 'text';

                if ($childNode->hasAttribute('data-type')) {
                    $dataType = $childNode->getAttribute('data-type');
                }

                $templateContext = $this->getTemplateContextStack()->top();
                $oldContextParent = $templateContext->getContextParent();
                $templateContext->setContextParent($toSort);

                // TODO: usorts can be optimized precalculating the strings first, or caching the strings.
                switch ($dataType) {
                    case 'text':
                        usort($newResults, function ($a, $b) use ($xPathParsed, $order) {
                            $result = $order * strcmp(
                                Converter::fromDOMToString($xPathParsed->evaluate($a)),
                                Converter::fromDOMToString($xPathParsed->evaluate($b))
                            );

                            if (!$result) {
                                if ($a->isSameNode($b)) {
                                    return 0;
                                }

                                $domNodeList = new DOMNodeList([$a, $b]);
                                $domNodeList->sort();

                                if ($domNodeList->item(0)->isSameNode($a)) {
                                    return -1;
                                }

                                return 1;
                            }

                            return $result;
                        });

                        break;

                    case 'number':
                        usort($newResults, function ($a, $b) use ($xPathParsed, $order) {
                            $result = 0;
                            $valueA = Converter::fromDOMToString($xPathParsed->evaluate($a));
                            $valueB = Converter::fromDOMToString($xPathParsed->evaluate($b));

                            if ($valueA < $valueB) {
                                $result = -1;
                            } elseif ($valueA > $valueB) {
                                $result = 1;
                            }

                            return $result * $order;
                        });
                }

                $templateContext->setContextParent($oldContextParent);

                $toSort = new DOMNodeList();
                $toSort->fromArray($newResults);
            }

            $this->debug->endNodeLevel($this->newXml);

            break;
        }

        return $toSort;
    }

    /**
     * xsl:strip-space.
     *
     * @param DOMElement $node
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     */
    protected function xslStripSpace(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $elements = $node->getAttribute('elements');
        $elements = explode(' ', $elements);
        $newElements = [];

        $namespaces = $this->getGlobalContext()->getNamespaces();

        foreach ($elements as $element) {
            $parts = explode(':', $element);

            if (count($parts) === 1) {
                $newElements[$namespaces[$this->getGlobalContext()->getDefaultNamespace()]][$parts[0]] = $parts[0];
            } else {
                $newElements[$namespaces[$parts[0]]][$parts[1]] = $parts[1];
            }
        }

        $xPathLeaves = $this->parseXPath('//*[not(*)]');
        $leaves = $xPathLeaves->evaluate($context);

        foreach ($leaves as $leaf) {
            /** @var $leaf DOMElement */
            if (
                (
                    isset($newElements[$leaf->namespaceURI][$leaf->localName])
                    || isset($newElements[$leaf->namespaceURI]['*'])
                )
                && preg_match('#^\s*$#', $leaf->nodeValue)
            ) {
                $leaf->nodeValue = '';
            }
        }
    }

    /**
     * xsl:message.
     *
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     * @throws MessageTerminatedException
     */
    protected function xslMessage(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        if ($node->hasAttribute('terminate')) {
            $terminate = $node->getAttribute('terminate');
        } else {
            $terminate = 'no';
        }

        $message = $this->evaluateBody($node, $context, $newContext)->evaluate();

        $this->messages[] = $message;

        if ($terminate === 'yes') {
            throw new MessageTerminatedException();
        }
    }

    /**
     * xsl:result-document.
     *
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     * @throws \RuntimeException
     */
    protected function xslResultDocument(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        if (!$node->hasAttribute('href')) {
            throw new RuntimeException('xsl:result-document without a name attribute not implemented');
        }

        $href = $this->evaluateAttrValueTemplates($node->getAttribute('href'), $context);

        if (!$node->hasAttribute('format')) {
            $output = new Output();
        } else {
            $output = clone $this->getGlobalContext()->getOutputs()[$this->evaluateAttrValueTemplates($node->getAttribute('format'), $context)];
        }

        if ($node->hasAttribute('method')) {
            $output->setMethod($this->evaluateAttrValueTemplates($node->getAttribute('method'), $context));
        }

        if ($node->hasAttribute('encoding')) {
            trigger_error('Attribute "encoding" not implemented in xsl:result-document', E_USER_NOTICE);
        }

        foreach ($node->attributes as $attribute) {
            switch ($attribute->nodeName) {
                case 'href':
                case 'format':
                case 'method':
                case 'encoding':
                    // Already treated
                    break;

                default:
                    $msg = 'Attribute "' . $attribute->nodeName . '" not implemented in xsl:result-document';
                    throw new RuntimeException($msg);
            }
        }

        $result = $this->evaluateBody($node, $context, $newContext);

        $document = new DOMDocument();

        foreach ($result->getBaseNode()->childNodes as $resultNode) {
            $document->appendChild($document->importNode($resultNode, true));
        }

        $result = $output->formatXml($document);

        // Save the result (only relative URLs for now)
        $parent = dirname($this->filePath);
        $absHref = $parent . '/' . $href;

        file_put_contents($absHref, $result);
    }

    /**
     * xsl:processing-instruction.
     *
     * @param DOMElement $node
     * @param DOMNode $context
     * @param DOMNode $newContext
     */
    protected function xslProcessingInstruction(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        $name = $node->getAttribute('name');
        $content = $this->evaluateBody($node, $context)->evaluate();

        $doc = ($context instanceof DOMDocument ? $context : $context->ownerDocument);

        $doc->appendChild($doc->createProcessingInstruction($name, $content));
    }

    /**
     * xsl:attribute-set.
     *
     * @param DOMNode    $context
     * @param DOMNode    $newContext
     * @param DOMElement $node
     */
    protected function xslAttributeSet(DOMElement $node, DOMNode $context, DOMNode $newContext)
    {
        // Get the name of the attribute set
        $name = $node->getAttribute('name');

        if ($node->hasAttribute('use-attribute-sets')) {
            throw new RuntimeException('Attribute use-attribute-sets not supported yet in xsl:attribute-set');
        }

        $set = [];

        // Process the childs to define and list the attributes
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->localName === 'attribute') {
                if ($childNode->hasAttribute('namespace')) {
                    throw new RuntimeException('Attribute namespace not supported yet in xsl:attribute');
                }

                $set[$childNode->getAttribute('name')] = $this->evaluateBody($childNode, $context)->evaluate();
            } elseif ($childNode instanceof DOMText && trim($childNode->nodeValue) === '') {
                continue;
            } else {
                throw new RuntimeException('Child ' . $childNode->nodeName . ' not recognized in xsl:attribute-set');
            }
        }

        $this->getGlobalContext()->getAttributeSets()->offsetSet($name, $set);
    }
}
