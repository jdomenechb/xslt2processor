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
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\FunctionImplementationInterface;
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
     * @var ExpressionInterface[]
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
        'fn' => 'fn',
        'http://exslt.org/common' => 'exslt',
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
     * @return ExpressionInterface[]
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

        if (!isset($this->availableNamespaces[$this->getNamespace()])) {
            throw new \RuntimeException('Namespace "' . $this->getNamespace() . '" not implemented for functions');
        }

        $className = __NAMESPACE__ . '\\FunctionImplementation';
        $className .= '\\' . ucfirst($this->availableNamespaces[$this->getNamespace()]);
        $className .= '\\' . implode('', array_map('ucfirst', explode('-', $this->getName())));

        if (!class_exists($className)) {
            $className = __NAMESPACE__ . '\\FunctionImplementation';
            $className .= '\\' . ucfirst($this->availableNamespaces[$this->getNamespace()]);
            $className .= '\\' . 'Func' . implode('', array_map('ucfirst', explode('-', $this->getName())));

            if (!class_exists($className)) {
                throw new RuntimeException('The function ' . $this->getFullName() . ' is not supported yet (' . $className . ')');
            }
        }

        /** @var $obj FunctionImplementationInterface */
        $obj = new $className();
        $result = $obj->evaluate($this, $context);

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

    /**
     * Return the keys generated by an XSLT transformation
     * @return mixed
     */
    public function getKeys()
    {
        return $this->keys;
    }
}
