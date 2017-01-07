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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\FunctionImplementationInterface;
use Jdomenechb\XSLT2Processor\XSLT\Context\GlobalContext;
use Jdomenechb\XSLT2Processor\XSLT\Context\TemplateContext;
use Jdomenechb\XSLT2Processor\XSLT\CustomFunction;
use Jdomenechb\XSLT2Processor\XSLT\Debug;
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

    /**
     * @var Debug
     */
    protected $debug;

    public function parse($string)
    {
        $eph = new Expression\ExpressionParserHelper();
        $parts = $eph->parseFirstLevelSubExpressions($string, '(', ')');

        if (count($parts) !== 3) {
            return false;
        }

        // Extract name
        $this->setFullName(array_shift($parts));

        // Parse parameters
        $factory = new Factory();

        $parametersRaw = array_shift($parts);

        if ($parametersRaw === '') {
            return true;
        }

        $parameters = $eph->explodeRootLevel(',', $parametersRaw);

        $parameters = array_map(function ($value) use ($factory) {
            return $factory->create($value);
        }, $parameters);

        $this->setParameters($parameters);

        return true;
    }

    public function setFullName($name)
    {
        $parts = explode(':', $name);

        if (count($parts) === 1) {
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

        return  $toReturn;
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
            throw new \RuntimeException('Custom functions are not supported yet');
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

        if ($this->getDebug()->isEnabled() && $this->getNamespacePrefix() !== 'exsl') {
            $this->getDebug()->showFunction($this->getFullName(), $result);
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


    public function setGlobalContext(GlobalContext $context)
    {
        parent::setGlobalContext($context);

        foreach ($this->getParameters() as $parameter) {
            $parameter->setGlobalContext($context);
        }
    }

    public function setTemplateContext(TemplateContext $context)
    {
        parent::setTemplateContext($context);

        foreach ($this->getParameters() as $parameter) {
            $parameter->setTemplateContext($context);
        }
    }

    /**
     * @return Debug
     */
    public function getDebug()
    {
        if (!$this->debug) {
            $this->setDebug(Debug::getInstance());
        }

        return $this->debug;
    }

    /**
     * @param Debug $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    protected function getNamespace()
    {
        if ($this->getNamespacePrefix() === 'fn') {
            return 'fn';
        }

        if (!isset($this->getGlobalContext()->getNamespaces()[$this->getNamespacePrefix()])) {
            throw new \RuntimeException('Namespace with prefix "' . $this->getNamespacePrefix() . '" not defined');
        }

        return $this->getGlobalContext()->getNamespaces()[$this->getNamespacePrefix()];
    }

    public function query($context)
    {
        $result = $this->evaluate($context);

        if (!$result instanceof DOMNodeList) {
            throw new \RuntimeException('Query must return a NodeSet: your xPath does not return a NodeSet');
        }

        return $result;
    }
}
