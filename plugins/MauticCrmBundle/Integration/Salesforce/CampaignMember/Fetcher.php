<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember;

use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Exception\InvalidObjectException;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Exception\NoObjectsToFetchException;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\CampaignMember;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Contact;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\QueryBuilder;

class Fetcher
{
    private array $leads = [];

    private array $knownLeadIds = [];

    private array $unknownLeadIds = [];

    private array $contacts = [];

    private array $knownContactIds = [];

    private array $unknownContactIds = [];

    private array $mauticIds = [];

    private array $knownCampaignMembers = [];

    /**
     * @param string $campaignId
     */
    public function __construct(
        private IntegrationEntityRepository $repo,
        private Organizer $organizer,
        private $campaignId
    ) {
        $this->fetchLeads();
        $this->fetchContacts();
    }

    /**
     * Return SF query to fetch the object information for a CampaignMember.
     *
     * @throws NoObjectsToFetchException
     * @throws InvalidObjectException
     */
    public function getQueryForUnknownObjects(array $fields, $object): string
    {
        return match ($object) {
            Lead::OBJECT    => QueryBuilder::getLeadQuery($fields, $this->unknownLeadIds),
            Contact::OBJECT => QueryBuilder::getContactQuery($fields, $this->unknownContactIds),
            default         => throw new InvalidObjectException(),
        };
    }

    /**
     * Fetch the Mautic contact IDs that are not already tracked as SF campaign members.
     */
    public function getUnknownCampaignMembers(): array
    {
        // First, find those already tracked as part of this campaign
        $this->fetchCampaignMembers();

        // Second, find newly created objects
        $this->fetchNewlyCreated();

        $mauticLeadIds = array_map(
            fn ($entity) => $entity['internal_entity_id'],
            $this->knownCampaignMembers
        );

        return array_values(array_diff($this->mauticIds, $mauticLeadIds));
    }

    /**
     * Fetch SF leads already identified.
     */
    private function fetchLeads(): void
    {
        if (!$campaignMembers = $this->organizer->getLeadIds()) {
            return;
        }

        $this->leads = $this->repo->getIntegrationsEntityId(
            'Salesforce',
            Lead::OBJECT,
            'lead',
            null,
            null,
            null,
            false,
            0,
            0,
            $campaignMembers
        );

        foreach ($this->leads as $lead) {
            $this->knownLeadIds[] = $lead['integration_entity_id'];
            $this->mauticIds[]    = $lead['internal_entity_id'];
        }

        $this->unknownLeadIds = array_values(array_diff($campaignMembers, $this->knownLeadIds));
    }

    /**
     * Fetch SF contacts already identified.
     */
    private function fetchContacts(): void
    {
        if (!$campaignMembers = $this->organizer->getContactIds()) {
            return;
        }

        $this->contacts = $this->repo->getIntegrationsEntityId(
            'Salesforce',
            Contact::OBJECT,
            'lead',
            null,
            null,
            null,
            false,
            0,
            0,
            $campaignMembers
        );

        foreach ($this->contacts as $contact) {
            $this->knownContactIds[] = $contact['integration_entity_id'];
            $this->mauticIds[]       = $contact['internal_entity_id'];
        }

        $this->unknownContactIds = array_values(array_diff($campaignMembers, $this->knownContactIds));
    }

    /**
     * Fetch SF campaign members already identified.
     */
    private function fetchCampaignMembers(): void
    {
        if (!$this->mauticIds) {
            return;
        }

        $this->knownCampaignMembers = $this->repo->getIntegrationsEntityId(
            'Salesforce',
            CampaignMember::OBJECT,
            'lead',
            $this->mauticIds,
            null,
            null,
            false,
            0,
            0,
            $this->campaignId
        );
    }

    /**
     * Fetch a list of all identified objects for SF contacts and leads.
     */
    private function fetchNewlyCreated(): void
    {
        if (!$allUnknownContacts = array_merge($this->unknownLeadIds, $this->unknownContactIds)) {
            return;
        }

        $newlyCreated = $this->repo->getIntegrationsEntityId(
            'Salesforce',
            null,
            'lead',
            null,
            null,
            null,
            false,
            0,
            0,
            $allUnknownContacts
        );

        foreach ($newlyCreated as $contact) {
            $this->knownContactIds[] = $contact['integration_entity_id'];
            $this->mauticIds[]       = $contact['internal_entity_id'];
        }
    }
}
