<?php

declare(strict_types=1);

/*
 * @copyright  2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Jan Kozak <galvani78@gmail.com
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * SegmentDictionaryGenerationEvent is dispatched while dictionary to transform frontend filters into
 *  translation understandable by segment service is run.
 *
 * This is not related to language translations at all
 */
class SegmentDictionaryGenerationEvent extends CommonEvent
{
    /**
     * @var array
     */
    private $translations;

    /**
     * SegmentDictionaryGenerationEvent constructor.
     *
     * @param array $translations
     */
    public function __construct($translations = [])
    {
        $this->translations = $translations;
    }

    /**
     * @param string $key
     * @param array  $attributes
     *
     * @return SegmentDictionaryGenerationEvent
     */
    public function addTranslation(string $key, $attributes)
    {
        $this->translations[$key] = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
