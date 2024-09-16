<?php

declare(strict_types=1);

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
     * @var array<string,mixed[]>
     */
    private array $translations;

    /**
     * @param array<string,mixed[]> $translations
     */
    public function __construct($translations = [])
    {
        $this->translations = $translations;
    }

    /**
     * @param mixed[] $attributes
     *
     * @return SegmentDictionaryGenerationEvent
     */
    public function addTranslation(string $key, $attributes)
    {
        $this->translations[$key] = $attributes;

        return $this;
    }

    /**
     * @return array<string,mixed[]>
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    public function hasTranslation(string $key): bool
    {
        return isset($this->translations[$key]);
    }
}
