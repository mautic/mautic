<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object;

class Contact
{
    public const OBJECT = 'Contact';

    public function __construct(
        private $id,
        private $campaignId,
        private $isDeleted,
    ) {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @return mixed
     */
    public function getisDeleted()
    {
        return $this->isDeleted;
    }
}
