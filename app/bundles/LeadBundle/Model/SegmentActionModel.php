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
    private $contactModel;

    /**
     * @param LeadModel $contactModel
     */
    public function __construct(LeadModel $contactModel)
    {
        $this->contactModel = $contactModel;
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

            $this->contactModel->addToLists($contact, $segmentIds);
        }

        $this->contactModel->saveEntities($contacts);
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

            $this->contactModel->removeFromLists($contact, $segmentIds);
        }

        $this->contactModel->saveEntities($contacts);
    }
}
