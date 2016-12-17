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

use DOMElement;
use DOMNodeList;
use Exception;
use Jdomenechb\XSLT2Processor\XSLT\CustomFunction;
use Jdomenechb\XSLT2Processor\XSLT\SystemProperties;
use RuntimeException;

class XPathFunction extends AbstractXPath
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected static $customFunctions = [];

    /**
     * @var string
     */
    protected $namespacePrefix = 'fn';

    /**
     * @var ExpressionInterface
     */
    protected $selector;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var array
     */
    protected $availableNamespaces = [
        'fn',
        'http://exslt.org/common',
    ];

    protected $keys;

    protected $defaultNamespacePrefix;

    /**
     * {@inheritdoc}
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    public function parse($string)
    {
        $eph = new Expression\ExpressionParserHelper();
        $parts = $eph->parseFirstLevelSubExpressions($string, '(', ')');

        // Extract name
        $this->setFullName(array_shift($parts));

        // Parse parameters
        $factory = new Factory();

        $parametersRaw = array_shift($parts);

        if ($parametersRaw === '') {
            return;
        }

        $parameters = $eph->explodeRootLevel(',', $parametersRaw);

        $parameters = array_map(function ($value) use ($factory) {
            return $factory->create($value);
        }, $parameters);

        $this->setParameters($parameters);

        // Selector
        $parts = $eph->parseFirstLevelSubExpressions(array_shift($parts), '[', ']');

        foreach ($parts as $part) {
            if (!$part) {
                continue;
            }

            if (preg_match('#^\d+$#', $part)) {
                $this->setPosition($part);
            } else {
                $this->setSelector($factory->create($part));
            }
        }
    }

    public function setFullName($name)
    {
        $parts = explode(':', $name);

        if (count($parts) == 1) {
            $this->setName($name);
        } else {
            $this->setNamespacePrefix($parts[0]);
            $this->setName($parts[1]);
        }
    }

    public function getFullName()
    {
        return $this->getNamespacePrefix() . ':' . $this->getName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getNamespacePrefix()
    {
        return $this->namespacePrefix;
    }

    public function setNamespacePrefix($namespace)
    {
        $this->namespacePrefix = $namespace;
    }

    public function toString()
    {
        $toReturn = $this->getFullName() . '(';
        $parameters = array_map(
            function (ExpressionInterface $value) {
                return $value->toString();
            },
            $this->getParameters()
        );

        $toReturn .= implode(', ', $parameters);
        $toReturn .= ')';

        if ($this->getSelector()) {
            $toReturn .= '[' . $this->getSelector()->toString() . ']';
        }

        if ($this->getPosition()) {
            $toReturn .= '[' . $this->getPosition() . ']';
        }

        return  $toReturn;
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        $this->defaultNamespacePrefix = $prefix;

        array_map(
            function (ExpressionInterface $value) use ($prefix) {
                return $value->setDefaultNamespacePrefix($prefix);
            },
            $this->getParameters()
        );

        if ($this->getSelector()) {
            $this->getSelector()->setDefaultNamespacePrefix($prefix);
        }
    }

    public function setVariableValues(array $values)
    {
        array_map(
            function (ExpressionInterface $value) use ($values) {
                return $value->setVariableValues($values);
            },
            $this->getParameters()
        );

        if ($this->getSelector()) {
            $this->getSelector()->setVariableValues($values);
        }
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function evaluate($context)
    {
        if (array_key_exists($this->getName(), static::getCustomFunctions())) {
            throw new Exception('Custom functions are not supported yet');
        }

        if (!in_array($this->getNamespace(), $this->availableNamespaces)) {
            throw new \RuntimeException('Namespace "' . $this->getNamespace() . '" not implemented for functions');
        }

        switch ($this->getNamespace()) {
            case 'fn':
                switch ($this->getName()) {
                    case 'string':
                        $result = $this->internalString($this->getParameters()[0]->evaluate($context));
                        break;

                    case 'normalize-space':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalString($value);
                        $value = trim($value);
                        $value = preg_replace('# +#', ' ', $value);

                        $result = $value;
                        break;

                    case 'string-length':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalString($value);

                        $result = mb_strlen($value);
                        break;

                    case 'substring':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalString($value);

                        $start = $this->getParameters()[1]->evaluate($context) - 1;

                        if ($start < 0) {
                            $start = 0;
                        }

                        if (isset($this->getParameters()[2])) {
                            $len = $this->getParameters()[2]->evaluate($context);

                            $result = mb_substr($value, $start, $len);
                            break;
                        }

                        $result = mb_substr($value, $start);
                        break;

                    case 'substring-before':
                        $haystack = $this->getParameters()[0]->evaluate($context);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context);
                        $needle = $this->internalString($needle);

                        if (($pos = mb_strpos($haystack, $needle)) === false) {
                            $result = '';
                            break;
                        }

                        $result = mb_substr($haystack, 0, $pos);
                        break;

                    case 'substring-after':
                        $haystack = $this->getParameters()[0]->evaluate($context);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context);
                        $needle = $this->internalString($needle);

                        if (($pos = mb_strpos($haystack, $needle)) === false) {
                            $result = '';
                            break;
                        }

                        $result = mb_substr($haystack, $pos + 1);
                        break;

                    case 'contains':
                        $haystack = $this->getParameters()[0]->evaluate($context);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context);
                        $needle = $this->internalString($needle);

                        $result = mb_strpos($haystack, $needle) !== false;
                        break;

                    case 'concat':
                        $values = array_map(function ($value) use ($context) {
                            $value = $value->evaluate($context);
                            $value = $this->internalString($value);

                            return $value;
                        }, $this->getParameters());

                        $result = implode('', $values);
                        break;

                    case 'replace':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalString($value);

                        $pattern = $this->getParameters()[1]->evaluate($context);
                        $pattern = $this->internalString($pattern);

                        $replacement = $this->getParameters()[2]->evaluate($context);
                        $replacement = $this->internalString($replacement);

                        $value = preg_replace('#' . str_replace('#', '\#', $pattern) . '#', $replacement, $value);

                        $result = $value;
                        break;

                    case 'starts-with':
                        $haystack = $this->getParameters()[0]->evaluate($context);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context);
                        $needle = $this->internalString($needle);

                        $result = mb_strpos($haystack, $needle) === 0;
                        break;

                    case 'upper-case':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalString($value);

                        $result = mb_strtoupper($value);
                        break;

                    case 'not':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalBoolean($value);

                        $result = !$value;
                        break;

                    case 'position':
                        // Iterate all siblings
                        $parent = $context->parentNode;
                        $i = 0;

                        foreach ($parent->childNodes as $childNode) {
                            if ($childNode instanceof DOMElement) {
                                ++$i;

                                if ($childNode->isSameNode($context)) {
                                    $result = $i;
                                    break;
                                }
                            }
                        }

                        if (is_int($result)) {
                            break;
                        }

                        throw new RuntimeException('No position could be found for the node');
                    case 'local-name':
                        if (count($this->getParameters()) > 0) {
                            $property = $this->getParameters()[0]->evaluate($context);

                            if (!$property->count()) {
                                $result = '';
                            } else {
                                $result = $property->item(0)->localName;
                            }
                        } else {
                            $result = $context->localName;
                        }
                        break;

                    case 'system-property':
                        $property = $this->getParameters()[0]->evaluate($context);
                        $property = $this->internalString($property);

                        $result = SystemProperties::getProperty($property);
                        break;

                    case 'count':
                        $property = $this->getParameters()[0]->evaluate($context);

                        $result = $property->count();
                        break;

                    case 'name':
                        $property = $this->getParameters()[0]->evaluate($context);

                        if (!$property->count()) {
                            $result = null;
                            break;
                        }

                        $result = $property->item(0)->nodeName;
                        break;

                    case 'translate':
                        $value = $this->getParameters()[0]->evaluate($context);
                        $value = $this->internalString($value);

                        $from = $this->getParameters()[1]->evaluate($context);
                        $from = $this->internalString($from);

                        $to = $this->getParameters()[2]->evaluate($context);
                        $to = $this->internalString($to);

                        $result = str_replace(str_split($from), str_split($to), $value);
                        break;

                    case 'generate-id':
                        if (!count($this->getParameters())) {
                            $value = $context;
                        } else {
                            $value = $this->getParameters()[0]->evaluate($context);
                        }

                        if ($value instanceof \Jdomenechb\XSLT2Processor\XML\DOMNodeList) {
                            if (!$value->count()) {
                                $result = '';
                                break;
                            }

                            $value = $value->item(0);
                        }

                        /* @var $value \DOMElement */
                        $result = 'n' . sha1($value->getNodePath());
                        break;

                    case 'key':
                        $keyName = $this->getParameters()[0]->evaluate($context);
                        $keyName = $this->internalString($keyName);

                        $toSearch = $this->getParameters()[1]->evaluate($context);
                        $toSearch = $this->internalString($toSearch);

                        if (!isset($this->keys[$keyName])) {
                            throw new \RuntimeException('The key named "' . $keyName . '" does not exist');
                        }

                        /* @var $key \Jdomenechb\XSLT2Processor\XSLT\Template\Key */
                        $key = $this->keys[$keyName];

                        // Get all possible nodes
                        $factory = new Factory();
                        $match = $factory->create($key->getMatch());
                        $match->setNamespaces($this->getNamespaces());
                        $match->setDefaultNamespacePrefix($this->getDefaultNamespacePrefix());

                        $possible = $factory->create($key->getUse() . " = '" . $toSearch . "'");
                        $possible->setNamespaces($this->getNamespaces());
                        $possible->setDefaultNamespacePrefix($this->getDefaultNamespacePrefix());

                        $possibleNodes = $match->evaluate($context);
                        $result = new \Jdomenechb\XSLT2Processor\XML\DOMNodeList();

                        foreach ($possibleNodes as $possibleNode) {
                            if ($possible->evaluate($possibleNode)) {
                                $result[] = $possibleNode;
                            }
                        }

                        break;

                    case 'false':
                        $result = false;
                        break;

                    case 'true':
                        $result = true;
                        break;

                    default:
                        throw new RuntimeException('Function "' . $this->getName() . '" not implemented');
                }
                break;

            case 'http://exslt.org/common':
                switch ($this->getName()) {
                    case 'node-set':
                        $property = $this->getParameters()[0]->evaluate($context);
                        $property->setParent(true);

                        $result = $property;
                        break;

                    default:
                        throw new RuntimeException('Function "' . $this->getName() . '" not implemented');
                }

                break;
        }

        if (\Jdomenechb\XSLT2Processor\XSLT\Processor::$debug && $this->getNamespacePrefix() != 'exsl') {
            echo 'Function ' . $this->getFullName() . ' result: <br>';
            var_dump($result);
        }

        if ($this->getPosition()) {
            if (!$result instanceof \Jdomenechb\XSLT2Processor\XML\DOMNodeList) {
                throw new \RuntimeException('Result of the function is not a node-set: position invalid');
            }

            return new \Jdomenechb\XSLT2Processor\XML\DOMNodeList($result->item(0));
        }

        return $result;
    }

    /**
     * @return array
     */
    public static function getCustomFunctions()
    {
        return self::$customFunctions;
    }

    /**
     * @param array $customFunctions
     */
    public static function setCustomFunctions($customFunctions)
    {
        self::$customFunctions = $customFunctions;
    }

    public static function setCustomFunction(CustomFunction $function)
    {
        self::$customFunctions[$function->getName()] = $function;
    }

    public function setNamespaces(array $namespaces)
    {
        parent::setNamespaces($namespaces);

        foreach ($this->getParameters() as $parameter) {
            $parameter->setNamespaces($namespaces);
        }

        if ($this->getSelector()) {
            $this->getSelector()->setNamespaces($namespaces);
        }
    }

    public function getSelector()
    {
        return $this->selector;
    }

    public function setSelector(ExpressionInterface $selector)
    {
        throw new \RuntimeException('To implement selector in result of a function');
        $this->selector = $selector;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setKeys(array $keys)
    {
        $this->keys = $keys;

        foreach ($this->getParameters() as $parameter) {
            $parameter->setKeys($keys);
        }

        if ($this->getSelector()) {
            $this->getSelector()->setKeys($keys);
        }
    }

    public function getDefaultNamespacePrefix()
    {
        return $this->defaultNamespacePrefix;
    }

    protected function getNamespace()
    {
        if ($this->getNamespacePrefix() == 'fn') {
            return 'fn';
        }

        if (!isset($this->getNamespaces()[$this->getNamespacePrefix()])) {
            throw new \RuntimeException('Namespace with prefix "' . $this->getNamespacePrefix() . '" not defined');
        }

        return $this->getNamespaces()[$this->getNamespacePrefix()];
    }

    protected function internalString($value)
    {
        if ($value instanceof DOMNodeList) {
            if ($value->length == 0) {
                return '';
            }

            return $value->item(0)->nodeValue;
        }

        if ($value instanceof \Jdomenechb\XSLT2Processor\XML\DOMNodeList) {
            if ($value->count() == 0) {
                return '';
            }

            return $value->item(0)->nodeValue;
        }

        return (string) $value;
    }

    protected function internalBoolean($value)
    {
        if ($value instanceof DOMNodeList) {
            if ($value->length == 0) {
                return false;
            }

            return true;
        }

        return (bool) $value;
    }
}
