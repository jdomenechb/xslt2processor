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
use DOMNode;
use DOMNodeList;
use DOMXPath;
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
     * @var array
     */
    protected $availableNamespaces = [
        'fn',
        'http://exslt.org/common'
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct($string)
    {
        $this->parse($string);
    }

    public function parse($string)
    {
        // Extract name
        $fParPos = strpos($string, '(');
        $this->setFullName(substr($string, 0, $fParPos));

        // Parse parameters
        $factory = new Factory();

        $parametersRaw = trim(substr($string, $fParPos + 1, -1));

        if ($parametersRaw === '') {
            return;
        }

        $parameters = $factory->parseByOperator(',', $parametersRaw);

        $parameters = array_map(function ($value) use ($factory) {
            return $factory->create($value);
        }, $parameters);
        $this->setParameters($parameters);
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

    public function getNamespace()
    {
        if ($this->getNamespacePrefix() == 'fn') {
            return 'fn';
        }

        if (!isset($this->getNamespaces()[$this->getNamespacePrefix()])) {
            throw new \RuntimeException('Namespace with prefix "' . $this->getNamespacePrefix() . '" not defined');
        }

        return $this->getNamespaces()[$this->getNamespacePrefix()];
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

        return  $toReturn;
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        array_map(
            function (ExpressionInterface $value) use ($prefix) {
                return $value->setDefaultNamespacePrefix($prefix);
            },
            $this->getParameters()
        );
    }

    public function setVariableValues(array $values)
    {
        array_map(
            function (ExpressionInterface $value) use ($values) {
                return $value->setVariableValues($values);
            },
            $this->getParameters()
        );
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

    public function evaluate($context, DOMXPath $xPathReference)
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
                        $result = $this->internalString($this->getParameters()[0]->evaluate($context, $xPathReference));
                        break;

                    case 'normalize-space':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $value = $this->internalString($value);
                        $value = trim($value);
                        $value = preg_replace('# +#', ' ', $value);

                        $result = $value;
                        break;

                    case 'string-length':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $value = $this->internalString($value);

                        $result = mb_strlen($value);
                        break;

                    case 'substring':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $value = $this->internalString($value);

                        $start = $this->getParameters()[1]->evaluate($context, $xPathReference) - 1;

                        if ($start < 0) {
                            $start = 0;
                        }

                        if (isset($this->getParameters()[2])) {
                            $len = $this->getParameters()[2]->evaluate($context, $xPathReference);

                            $result = mb_substr($value, $start, $len);
                            break;
                        }

                        $result = mb_substr($value, $start);
                        break;

                    case 'substring-before':
                        $haystack = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context, $xPathReference);
                        $needle = $this->internalString($needle);

                        if (mb_strpos($haystack, $needle) === false) {
                            $result = '';
                            break;
                        }

                        $result = mb_substr($haystack, 0, mb_strpos($haystack, $needle));
                        break;

                    case 'contains':
                        $haystack = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context, $xPathReference);
                        $needle = $this->internalString($needle);

                        $result = mb_strpos($haystack, $needle) !== false;
                        break;

                    case 'concat':
                        $values = array_map(function ($value) use ($context, $xPathReference) {
                            $value = $value->evaluate($context, $xPathReference);
                            $value = $this->internalString($value);

                            return $value;
                        }, $this->getParameters());

                        $result = implode('', $values);
                        break;

                    case 'replace':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $value = $this->internalString($value);

                        $pattern = $this->getParameters()[1]->evaluate($context, $xPathReference);
                        $pattern = $this->internalString($pattern);

                        $replacement = $this->getParameters()[2]->evaluate($context, $xPathReference);
                        $replacement = $this->internalString($replacement);

                        $value = preg_replace('#' . str_replace('#', '\#', $pattern) . '#', $replacement, $value);

                        $result = $value;
                        break;

                    case 'starts-with':
                        $haystack = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $haystack = $this->internalString($haystack);

                        $needle = $this->getParameters()[1]->evaluate($context, $xPathReference);
                        $needle = $this->internalString($needle);

                        $result = mb_strpos($haystack, $needle) === 0;
                        break;

                    case 'upper-case':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $value = $this->internalString($value);

                        $result = mb_strtoupper($value);
                        break;

                    case 'not':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
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
                            $property = $this->getParameters()[0]->evaluate($context, $xPathReference);

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
                        $property = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $property = $this->internalString($property);

                        $result = SystemProperties::getProperty($property);
                        break;

                    case 'count':
                        $property = $this->getParameters()[0]->evaluate($context, $xPathReference);

                        $result = $property->count();
                        break;

                    case 'name':
                        $property = $this->getParameters()[0]->evaluate($context, $xPathReference);

                        if (!$property->count()) {
                            $result = null;
                            break;
                        }

                        $result = $property->item(0)->nodeName;
                        break;

                    case 'translate':
                        $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                        $value = $this->internalString($value);

                        $from = $this->getParameters()[1]->evaluate($context, $xPathReference);
                        $from = $this->internalString($from);

                        $to = $this->getParameters()[2]->evaluate($context, $xPathReference);
                        $to = $this->internalString($to);

                        $result = str_replace(str_split($from), str_split($to), $value);
                        break;

                    default:
                        throw new RuntimeException('Function "' . $this->getName() . '" not implemented');
                }
                break;

            case 'http://exslt.org/common':
                switch ($this->getName()) {
                    case 'node-set':
                        $property = $this->getParameters()[0]->evaluate($context, $xPathReference);
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

    public function setNamespaces(array $namespaces)
    {
        parent::setNamespaces($namespaces);

        foreach ($this->getParameters() as $parameter) {
            $parameter->setNamespaces($namespaces);
        }
    }
}
