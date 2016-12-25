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
    /**
     * Instance for the singleton pattern.
     *
     * @var self
     */
    protected static $instance;

    /**
     * Determines if the debug is enabled.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Output class of the processor, to determine the format of output debug.
     *
     * @var Output
     */
    protected $output;

    /**
     * Debug constructor.
     */
    private function __construct()
    {
    }

    /**
     * Prints the end of a level, with the "after" changes.
     *
     * @param \DOMDocument $xml
     */
    public function endNodeLevel(\DOMDocument $xml)
    {
        if ($this->isEnabled()) {
            echo '<h3>&lt;/evaluation&gt;</h3>';
            echo '<h3>After</h3>';
            echo '<pre>' . htmlspecialchars($this->getXml($xml)) . '</pre>';
            echo '</div>';
        }
    }

    /**
     * Prints the start of a level, with the info of the node provided and the "before" status.
     *
     * @param \DOMDocument $xml
     * @param \DOMNode     $node
     */
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

    /**
     * Prints information about a given template.
     *
     * @param Template $template
     */
    public function showTemplate(Template $template)
    {
        if ($this->isEnabled()) {
            echo '<p>Template: ';
            echo '@name="' . $template->getName() . '"';
            echo ' ### @match="' . $template->getMatch() . '"';
            echo '</p>';
        }
    }

    /**
     * Prints the result of a function.
     *
     * @param $name
     * @param $result
     */
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
     * Get output class of the processor, to determine the format of output debug.
     *
     * @param Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Get a Singleton instance.
     *
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
     * Get the XML string of the document.
     *
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
