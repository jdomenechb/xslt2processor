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

use Jdomenechb\XSLT2Processor\XML\DOMNodeList;
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

    protected $includeBefore = false;

    protected $includeAfter = false;

    /**
     * Output class of the processor, to determine the format of output debug.
     *
     * @var Output
     */
    protected $output;

    /**
     * Stream to direct the output to.
     *
     * @var resource
     */
    protected $stream;

    /**
     * Debug constructor.
     */
    protected function __construct()
    {
        $this->setStream(fopen('php://output', 'wb'));
    }

    /**
     * Prints the end of a level, with the "after" changes.
     *
     * @param \DOMDocument $xml
     */
    public function endNodeLevel(\DOMDocument $xml)
    {
        if ($this->isEnabled()) {
            fwrite($this->getStream(), '<h3>&lt;/evaluation&gt;</h3>');

            if ($this->isIncludeAfter()) {
                fwrite($this->getStream(), '<h3>After</h3>');
                fwrite($this->getStream(), '<pre>' . htmlspecialchars($this->getXml($xml)) . '</pre>');
            }

            fwrite($this->getStream(), '</div>');
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
            fwrite($this->getStream(), '<div style="border-left: 1px solid #555; border-top: 1px solid #555; '
                . 'border-bottom: 1px solid #555; padding-left: 2px; margin-left: 20px">');
            fwrite($this->getStream(), "<h2 style='font-family: monospace;'>$node->nodeName</h2>");

            $attr = [];

            foreach ($node->attributes as $attribute) {
                $attr[] = '@' . $attribute->name . '="' . $attribute->value . '"';
            }

            if (count($attr)) {
                fwrite(
                    $this->getStream(),
                    '<span style="font-family: monospace; font-size: 1.5em;">' . implode(' ### ', $attr) . '</span>'
                );
                fwrite($this->getStream(), '<br>');
            }

            if ($this->isIncludeBefore()) {
                fwrite($this->getStream(), '<h3>Before</h3>');
                fwrite($this->getStream(), '<pre>' . htmlspecialchars($this->getXml($xml)) . '</pre>');
            }

            fwrite($this->getStream(), '<h3>&lt;evaluation&gt;</h3>');
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
            fwrite($this->getStream(), '<p>Template: ');
            fwrite($this->getStream(), '@name="' . $template->getName() . '"');
            fwrite($this->getStream(), ' ### @match="' . $template->getMatch() . '"');
            fwrite($this->getStream(), '</p>');
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
            fwrite($this->getStream(), '<p>Function ' . $name . ' result:</p>');

            /* @noinspection ForgottenDebugOutputInspection */
            ob_start();
            var_dump($result);
            $toDisplay = ob_get_clean();

            fwrite($this->getStream(), $toDisplay);
        }
    }

    public function showVar($varName, $varValue)
    {
        if ($this->isEnabled()) {
            fwrite($this->getStream(), '<p>Variable "' . $varName . '" content:</p>');

            /* @noinspection ForgottenDebugOutputInspection */
            ob_start();
            var_dump($varValue);
            $toDisplay = ob_get_clean();

            fwrite($this->getStream(), $toDisplay);

            if ($varValue instanceof DOMNodeList || $varValue instanceof \DOMNodeList) {
                foreach ($varValue as $node) {
                    fwrite($this->getStream(), '<pre>' . htmlentities(
                        $this->getOutput()->getMethod() == Output::METHOD_XML ?
                        $node->ownerDocument->saveXML($node) :
                        $node->ownerDocument->saveHTML($node)
                    ) . '</pre>');
                }
            }
        }
    }

    public function printText($text)
    {
        if ($this->isEnabled()) {
            fwrite($this->getStream(), '<p>' . $text . '</p>');
        }
    }

    /**
     * Displays verbosely the given parameter.
     *
     * @param $content
     */
    public function show($content)
    {
        if ($this->isEnabled()) {
            ob_start();
            var_dump($content);
            $toDisplay = ob_get_clean();

            fwrite($this->getStream(), '<p>' . $toDisplay . '</p>');
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
     * @return bool
     */
    public function isIncludeBefore()
    {
        return $this->includeBefore;
    }

    /**
     * @param bool $includeBefore
     */
    public function setIncludeBefore($includeBefore)
    {
        $this->includeBefore = $includeBefore;
    }

    /**
     * @return bool
     */
    public function isIncludeAfter()
    {
        return $this->includeAfter;
    }

    /**
     * @param bool $includeAfter
     */
    public function setIncludeAfter($includeAfter)
    {
        $this->includeAfter = $includeAfter;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param resource $stream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
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
