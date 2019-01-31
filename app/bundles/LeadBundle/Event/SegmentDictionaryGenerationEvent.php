<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

class SegmentDictionaryGenerationEvent extends CommonEvent
{
    /**
     * @var array
     */
    private $translations;

    public function __construct(array $translations = [])
    {
        $this->translations = $translations;
    }

    public function addTranslation($key, $attributes): self
    {
        $this->translations[$key] = $attributes;

        return $this;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }
}
