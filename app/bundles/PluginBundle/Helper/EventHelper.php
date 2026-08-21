<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Helper;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

final class EventHelper
{
    use PushToIntegrationTrait;

    public function __construct(
        private readonly LeadRepository $leadRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function pushLead(array $config, $lead): bool
    {
        $contact = $this->leadRepository->getEntityWithPrimaryCompany($lead);

        $errors = [];

        return self::pushIt($config, $contact, $errors);
    }
}
