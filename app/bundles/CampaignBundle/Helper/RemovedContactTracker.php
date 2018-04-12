<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

class RemovedContactTracker
{
    /**
     * @var array
     */
    private $removedContacts = [];

    /**
     * @param $campaignId
     * @param $contactId
     */
    public function addRemovedContact($campaignId, $contactId)
    {
        if (!isset($this->removedContacts[$campaignId])) {
            $this->removedContacts[$campaignId] = [];
        }

        $this->removedContacts[$campaignId][$contactId] = $contactId;
    }

    /**
     * @param $campaignId
     */
    public function wasContactRemoved($campaignId, $contactId)
    {
        return !empty($this->removedContacts[$campaignId][$contactId]);
    }

    /**
     * @return array
     */
    public function getRemovedContacts()
    {
        return $this->removedContacts;
    }
}
