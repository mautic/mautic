<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    /**
     * @var IntegrationEntityRepository
     */
    private $repo;

    /**
     * @var Organizer
     */
    private $organizer;

    /**
     * @var string
     */
    private $campaignId;

    /**
     * @var array
     */
    private $leads = [];

    /**
     * @var array
     */
    private $knownLeadIds = [];

    /**
     * @var array
     */
    private $unknownLeadIds = [];

    /**
     * @var array
     */
    private $contacts = [];

    /**
     * @var array
     */
    private $knownContactIds = [];

    /**
     * @var array
     */
    private $unknownContactIds = [];

    /**
     * @var array
     */
    private $mauticIds = [];

    /**
     * @var array
     */
    private $knownCampaignMembers = [];

    /**
     * Fetcher constructor.
     *
     * @param string $campaignId
     */
    public function __construct(IntegrationEntityRepository $repo, Organizer $organizer, $campaignId)
    {
        $this->repo       = $repo;
        $this->organizer  = $organizer;
        $this->campaignId = $campaignId;

        $this->fetchLeads();
        $this->fetchContacts();
    }

    /**
     * Return SF query to fetch the object information for a CampaignMember.
     *
     * @param $object
     *
     * @return string
     *
     * @throws NoObjectsToFetchException
     * @throws InvalidObjectException
     */
    public function getQueryForUnknownObjects(array $fields, $object)
    {
        switch ($object) {
            case Lead::OBJECT:
                return QueryBuilder::getLeadQuery($fields, $this->unknownLeadIds);
            case Contact::OBJECT:
                return QueryBuilder::getContactQuery($fields, $this->unknownContactIds);
            default:
                throw new InvalidObjectException();
        }
    }

    /**
     * Fetch the Mautic contact IDs that are not already tracked as SF campaign members.
     *
     * @return array
     */
    public function getUnknownCampaignMembers()
    {
        // First, find those already tracked as part of this campaign
        $this->fetchCampaignMembers();

        // Second, find newly created objects
        $this->fetchNewlyCreated();

        $mauticLeadIds = array_map(
            function ($entity) {
                return $entity['internal_entity_id'];
            },
            $this->knownCampaignMembers
        );

        return array_values(array_diff($this->mauticIds, $mauticLeadIds));
    }

    /**
     * Fetch SF leads already identified.
     */
    private function fetchLeads()
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
    private function fetchContacts()
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
    private function fetchCampaignMembers()
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
    private function fetchNewlyCreated()
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
