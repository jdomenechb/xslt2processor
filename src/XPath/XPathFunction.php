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
        $this->setName(substr($string, 0, $fParPos));

        // Parse parameters
        $factory = new Factory();

        $parametersRaw = substr($string, $fParPos + 1, -1);
        $parameters = $factory->parseByOperator(',', $parametersRaw);

        $parameters = array_map(function ($value) use ($factory) {
            return $factory->create($value);
        }, $parameters);
        $this->setParameters($parameters);
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

    public function toString()
    {
        $toReturn = $this->getName() . '(';
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

    public function evaluate(DOMNode $context, DOMXPath $xPathReference)
    {
        if (array_key_exists($this->getName(), static::getCustomFunctions())) {
            throw new Exception('Custom functions are not supported yet');
            return;
        }

        switch ($this->getName()) {
            case 'string':
                return $this->internalString($this->getParameters()[0]->evaluate($context, $xPathReference));

            case 'normalize-space':
                $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $value = $this->internalString($value);
                $value = trim($value);
                $value = preg_replace('# +#', ' ', $value);

                return $value;

            case 'string-length':
                $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $value = $this->internalString($value);

                return mb_strlen($value);

            case 'substring':
                $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $value = $this->internalString($value);

                $start = $this->getParameters()[1]->evaluate($context, $xPathReference);

                if (isset($this->getParameters()[2])) {
                    $end = $this->getParameters()[2]->evaluate($context, $xPathReference);

                    return mb_substr($value, $start, $end);
                }

                return mb_substr($value, $start);

            case 'substring-before':
                $haystack = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $haystack = $this->internalString($haystack);

                $needle = $this->getParameters()[1]->evaluate($context, $xPathReference);
                $needle = $this->internalString($needle);

                if (mb_strpos($haystack, $needle) === false) {
                    return '';
                }

                return mb_substr($haystack, 0, mb_strpos($haystack, $needle));

            case 'contains':
                $haystack = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $haystack = $this->internalString($haystack);

                $needle = $this->getParameters()[1]->evaluate($context, $xPathReference);
                $needle = $this->internalString($needle);

                return mb_strpos($haystack, $needle) !== false;

            case 'concat':
                $values = array_map(function ($value) use ($context, $xPathReference) {
                    $value = $value->evaluate($context, $xPathReference);
                    $value = $this->internalString($value);

                    return $value;
                }, $this->getParameters());

                return implode('', $values);

            case 'replace':
                $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $value = $this->internalString($value);

                $pattern = $this->getParameters()[1]->evaluate($context, $xPathReference);
                $pattern = $this->internalString($pattern);

                $replacement = $this->getParameters()[2]->evaluate($context, $xPathReference);
                $replacement = $this->internalString($replacement);

                $value = preg_replace('#' . str_replace('#', '\#', $pattern) . '#', $replacement, $value);

                return $value;

            case 'starts-with':
                $haystack = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $haystack = $this->internalString($haystack);

                $needle = $this->getParameters()[1]->evaluate($context, $xPathReference);
                $needle = $this->internalString($needle);

                return mb_strpos($haystack, $needle) === 0;

            case 'upper-case':
                $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $value = $this->internalString($value);

                return mb_strtoupper($value);

            case 'not':
                $value = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $value = $this->internalBoolean($value);

                return !$value;

            case 'position':
                // Iterate all siblings
                $parent = $context->parentNode;
                $i = 0;

                foreach ($parent->childNodes as $childNode) {
                    if ($childNode instanceof DOMElement) {
                        ++$i;

                        if ($childNode->isSameNode($context)) {
                            return $i;
                        }
                    }
                }

                throw new RuntimeException('No position could be found for the node');
            case 'local-name':
                return $context->localName;

            case 'system-property':
                $property = $this->getParameters()[0]->evaluate($context, $xPathReference);
                $property = $this->internalString($property);

                return SystemProperties::getProperty($property);

            case 'count':
                $property = $this->getParameters()[0]->evaluate($context, $xPathReference);

                return $property->length;

            default:
                throw new RuntimeException('Function "' . $this->getName() . '" not implemented');
        }
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
