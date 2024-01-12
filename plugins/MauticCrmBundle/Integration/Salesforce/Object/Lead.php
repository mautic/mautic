<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object;

class Lead
{
    public const OBJECT = 'Lead';

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
