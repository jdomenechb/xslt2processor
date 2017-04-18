<?php

/**
 * This file is part of the XSLT2Processor package.
 *
 * (c) Jordi Domènech Bonilla
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jdomenechb\XSLT2Processor\XSLT\Template;

use ArrayObject;

/**
 * Class that represents a list of templates.
 *
 * @author jdomenechb
 */
class TemplateList extends ArrayObject
{
    protected $nameRelation = [];

    /**
     * Gets a list of templates that have the given name.
     *
     * @param mixed $name
     *
     * @return TemplateList
     */
    public function getByName($name)
    {
        return new self(isset($this->nameRelation[$name])? $this->nameRelation[$name]: []);
    }

    public function appendTemplate(Template $template)
    {
        $count = count($this);

        for ($i = 0; $i < $count; ++$i) {
            $currentTemplate = $this[$i];

            if ($template->getPriority() > $currentTemplate->getPriority()) {
                $newTemplates = array_slice($this->getArrayCopy(), 0, $i);
                $newTemplates[] = $template;
                $newTemplates = array_merge($newTemplates, array_slice($this->getArrayCopy(), $i));

                $this->exchangeArray($newTemplates);
                $this->nameRelation[$template->getName()][] = $template;

                return;
            }
        }

        parent::append($template);

        $this->nameRelation[$template->getName()][] = $template;

    }

    public function append($template)
    {
        return $this->appendTemplate($template);
    }
}
