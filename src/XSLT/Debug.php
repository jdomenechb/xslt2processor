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

use Jdomenechb\XSLT2Processor\XSLT\Template\Template;

/**
 * Helps with the output debug information in XSLT2Processor.
 */
class Debug
{
    protected static $instance;
    protected $enabled = false;

    /**
     * @var Output
     */
    protected $output;

    private function __construct()
    {
    }

    public function endNodeLevel(\DOMDocument $xml)
    {
        if ($this->isEnabled()) {
            echo '<h3>&lt;/evaluation&gt;</h3>';
            echo '<h3>After</h3>';
            echo '<pre>' . htmlspecialchars($this->getXml($xml)) . '</pre>';
            echo '</div>';
        }
    }

    public function startNodeLevel(\DOMDocument $xml, \DOMNode $node)
    {
        if ($this->isEnabled()) {
            echo '<div style="border-left: 1px solid #555; border-top: 1px solid #555; border-bottom: 1px solid #555; '
                . 'padding-left: 2px; margin-left: 20px">';
            echo "<h2 style='font-family: monospace;'>$node->nodeName</h2>";

            $attr = [];

            foreach ($node->attributes as $attribute) {
                $attr[] = '@' . $attribute->name . '="' . $attribute->value . '"';
            }

            if (count($attr)) {
                echo '<span style="font-family: monospace; font-size: 1.5em;">' . implode(' ### ', $attr) . '</span>';
                echo '<br>';
            }

            echo '<h3>Before</h3>';
            echo '<pre>' . htmlspecialchars($this->getXml($xml)) . '</pre>';
            echo '<h3>&lt;evaluation&gt;</h3>';
        }
    }

    public function showTemplate(Template $template)
    {
        if ($this->isEnabled()) {
            echo '<p>Chosen template: ';
            echo '@name="' . $template->getName() . '"';
            echo ' ### @match="' . $template->getMatch() . '"';
            echo '</p>';
        }
    }

    public function showFunction($name, $result)
    {
        if ($this->isEnabled()) {
            echo '<p>Function ' . $name . ' result:</p>';
            var_dump($result);
        }
    }

    /**
     * Get if the debug is enabled or not.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set the debug to enabled or disabled.
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            $className = self::class;
            self::$instance = new $className();
        }

        return self::$instance;
    }

    /**
     * @param \DOMDocument $xml
     *
     * @return string
     */
    protected function getXml(\DOMDocument $xml)
    {
        return $this->getOutput()->getMethod() == Output::METHOD_XML ?
            $xml->saveXML() :
            $xml->saveHTML();
    }
}
