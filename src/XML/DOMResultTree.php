<?php

/**
 * CORDIS Frontend application
 * 2017 - Developed by Lot 1 for CORDIS
 * @author Lot 1 - S2 - everis Barcelona
 */

namespace Jdomenechb\XSLT2Processor\XML;


class DOMResultTree
{
    /**
     * @var \DOMElement
     */
    protected $baseNode;

    public function __construct(\DOMDocument $xml)
    {
        $this->setBaseNode($xml->createElement('tmptmptmptmptmpevaluateBody' . mt_rand(0, 9999999)));
    }

    /**
     * @return \DOMElement
     */
    public function getBaseNode()
    {
        return $this->baseNode;
    }

    /**
     * @param \DOMElement $baseNode
     */
    public function setBaseNode($baseNode)
    {
        $this->baseNode = $baseNode;
    }

    public function evaluate()
    {
        if ($this->getBaseNode()->childNodes->length === 1) {
            if ($this->getBaseNode()->childNodes->item(0) instanceof \DOMText) {
                return $this->getBaseNode()->childNodes->item(0)->nodeValue;
            }

            return new DOMNodeList($this->getBaseNode()->childNodes->item(0));
        }

        if ($this->getBaseNode()->childNodes->length > 1) {
            $allText = true;
            $result = '';

            foreach ($this->getBaseNode()->childNodes as $childNode) {
                if (!$childNode instanceof \DOMCharacterData) {
                    $allText = false;
                    break;
                }

                $result .= $childNode->nodeValue;
            }

            if ($allText) {
                return $result;
            }

            return $this->getBaseNode()->childNodes;
        }

        return null;
    }
}
