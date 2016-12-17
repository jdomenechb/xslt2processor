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
    /**
     * Gets a list of templates that have the given match.
     *
     * @return TemplateList
     * @param  mixed        $match
     */
    public function getByMatch($match)
    {
        $values = array_values(array_filter($this->getArrayCopy(), function (Template $value) use ($match) {
            return $value->getMatch() == $match;
        }));

        $class = static::class;

        return new $class($values);
    }

    /**
     * Gets a list of templates that have the given name.
     *
     * @return TemplateList
     * @param  mixed        $name
     */
    public function getByName($name)
    {
        $values = array_values(array_filter($this->getArrayCopy(), function (Template $value) use ($name) {
            return $value->getName() == $name;
        }));

        $class = static::class;

        return new $class($values);
    }

    public function appendTemplate(Template $template)
    {
        for ($i = 0; $i < count($this); ++$i) {
            $currentTemplate = $this[$i];

            if ($template->getPriority() > $currentTemplate->getPriority()) {
                $newTemplates = array_slice($this->getArrayCopy(), 0, $i);
                $newTemplates[] = $template;
                $newTemplates = array_merge($newTemplates, array_slice($this->getArrayCopy(), $i));

                $this->exchangeArray($newTemplates);

                return;
            }
        }

        parent::append($template);
    }

    public function append($template)
    {
        return $this->appendTemplate($template);
    }
}
