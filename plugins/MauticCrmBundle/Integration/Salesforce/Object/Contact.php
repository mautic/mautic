<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object;

class Contact
{
    public const OBJECT = 'Contact';

    public function __construct(
        private $id,
        private $campaignId,
        private $isDeleted
    ) {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCampaignId()
    {
        return $this->campaignId;
    }

    public function getisDeleted()
    {
        return $this->isDeleted;
    }
}
