<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object;

class Contact
{
    const OBJECT = 'Contact';

    private $id;
    private $campaignId;
    private $isDeleted;

    public function __construct($id, $campaignId, $isDeleted)
    {
        $this->id         = $id;
        $this->campaignId = $campaignId;
        $this->isDeleted  = $isDeleted;
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
