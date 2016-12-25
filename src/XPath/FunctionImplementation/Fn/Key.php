<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\Fn;

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
use Jdomenechb\XSLT2Processor\XPath\Factory;
use Jdomenechb\XSLT2Processor\XPath\FunctionImplementation\AbstractFunctionImplementation;
use Jdomenechb\XSLT2Processor\XPath\XPathFunction;

/**
 * Function key() from XSLT standard library.
 */
class Key extends AbstractFunctionImplementation
{
    /**
     * {@inheritdoc}
     *
     * @param XPathFunction $func
     * @param $context
     *
     * @return string
     */
    public function evaluate(XPathFunction $func, $context)
    {
        $keyName = $func->getParameters()[0]->evaluate($context);
        $keyName = $this->valueAsString($keyName);

        $toSearch = $func->getParameters()[1]->evaluate($context);
        $toSearch = $this->valueAsString($toSearch);

        if (!isset($func->getKeys()[$keyName])) {
            throw new \RuntimeException('The key named "' . $keyName . '" does not exist');
        }

        /* @var $key \Jdomenechb\XSLT2Processor\XSLT\Template\Key */
        $key = $func->getKeys()[$keyName];

        // Get all possible nodes
        $factory = new Factory();
        $match = $factory->create($key->getMatch());
        $match->setNamespaces($func->getNamespaces());
        $match->setDefaultNamespacePrefix($func->getDefaultNamespacePrefix());

        $possible = $factory->create($key->getUse() . " = '" . $toSearch . "'");
        $possible->setNamespaces($func->getNamespaces());
        $possible->setDefaultNamespacePrefix($func->getDefaultNamespacePrefix());

        $possibleNodes = $match->evaluate($context);
        $result = new DOMNodeList();

        foreach ($possibleNodes as $possibleNode) {
            if ($possible->evaluate($possibleNode)) {
                $result[] = $possibleNode;
            }
        }

        return $result;
    }
}
