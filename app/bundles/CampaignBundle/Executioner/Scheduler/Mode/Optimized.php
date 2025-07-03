<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Services\PeakInteractionTimer;

class Optimized implements ScheduleModeInterface
{
    public const OPTIMIZED_TIME         = 0;
    public const OPTIMIZED_DAY_AND_TIME = 1;

    /** @var string[] */
    public const AVAILABLE_FOR_EVENTS = ['email.send', 'message.send', 'plugin.leadpush', 'campaign.sendwebhook'];

    public function __construct(
        private PeakInteractionTimer $peakInteractionTimer,
    ) {
    }

    public function getExecutionDateTime(Event $event, \DateTimeInterface $now, \DateTimeInterface $comparedToDateTime): \DateTimeInterface
    {
        return $now;
    }

    public function getExecutionDateTimeForContact(Event $event, Lead $contact): \DateTimeInterface
    {
        if (self::OPTIMIZED_DAY_AND_TIME === $event->getTriggerWindow()) {
            return $this->peakInteractionTimer->getOptimalTimeAndDay($contact);
        } else {
            return $this->peakInteractionTimer->getOptimalTime($contact);
        }
    }
}
