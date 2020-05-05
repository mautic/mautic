<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Model;

use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Translation\TranslatorInterface;

class ChannelActionModel
{
    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param LeadModel           $contactModel
     * @param DoNotContact        $doNotContact
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LeadModel $contactModel,
        DoNotContact $doNotContact,
        TranslatorInterface $translator
    ) {
        $this->contactModel = $contactModel;
        $this->doNotContact = $doNotContact;
        $this->translator   = $translator;
    }

    /**
     * Update channels and frequency rules.
     *
     * @param array $contactIds
     * @param array $subscribedChannels
     */
    public function update(array $contactIds, array $subscribedChannels)
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->addChannels($contact, $subscribedChannels);
            $this->removeChannels($contact, $subscribedChannels);
        }
    }

    /**
     * Add contact's channels.
     * Only resubscribe if the contact did not opt out themselves.
     *
     * @param Lead  $contact
     * @param array $subscribedChannels
     */
    private function addChannels(Lead $contact, array $subscribedChannels)
    {
        $contactChannels = $this->contactModel->getContactChannels($contact);

        foreach ($subscribedChannels as $subscribedChannel) {
            if (!array_key_exists($subscribedChannel, $contactChannels)) {
                $contactable = $this->doNotContact->isContactable($contact, $subscribedChannel);
                if ($contactable !== DNC::UNSUBSCRIBED) {
                    $this->doNotContact->removeDncForContact($contact->getId(), $subscribedChannel);
                }
            }
        }
    }

    /**
     * Remove contact's channels.
     *
     * @param Lead  $contact
     * @param array $subscribedChannels
     */
    private function removeChannels(Lead $contact, array $subscribedChannels)
    {
        $allChannels = $this->contactModel->getPreferenceChannels();
        $dncChannels = array_diff($allChannels, $subscribedChannels);

        if (empty($dncChannels)) {
            return;
        }

        foreach ($dncChannels as $channel) {
            $this->doNotContact->addDncForContact(
                $contact->getId(),
                $channel,
                DNC::MANUAL,
                $this->translator->trans('mautic.lead.event.donotcontact_manual')
            );
        }
    }
}
