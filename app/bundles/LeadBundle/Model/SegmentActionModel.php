<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

class SegmentActionModel
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @param array $contactIds
     * @param array $segmentIds
     */
    public function addContacts(array $contactIds, array $segmentIds)
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->leadModel->addToLists($contact, $segmentIds);
        }

        $this->leadModel->saveEntities($contacts);
    }

    /**
     * @param array $contactIds
     * @param array $segmentIds
     */
    public function removeContacts(array $contactIds, array $segmentIds)
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->leadModel->removeFromLists($contact, $segmentIds);
        }

        $this->leadModel->saveEntities($contacts);
    }
}
