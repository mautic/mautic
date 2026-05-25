<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Enum;

/**
 * Defines how the scheduled events behave after a campaign is republished. It has 3 options:
 * - `count_all_time`: There is no counting. The original trigger date is used. The unpublished time is not counted.
 * - `restart_on_publish`: The interval starts from scratch after the campaign is published again.
 * - `count_only_while_published`: The event will only count the time while the campaign is published. If it is unpublished, the counter will not increase.
 */
enum RepublishBehavior: string
{
    case RESTART_ON_PUBLISH         = 'restart_on_publish';
    case COUNT_ONLY_WHILE_PUBLISHED = 'count_only_while_published';
    case COUNT_ALL_TIME             = 'count_all_time';

    public function getLabel(): string
    {
        return 'mautic.campaignconfig.campaign_republish_behavior.'.$this->value;
    }

    /**
     * Returns all choices as an array suitable for Symfony forms.
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->value;
        }

        return $choices;
    }
}
