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

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModel
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $permissions;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    /**
     * ChangeChannelsAction constructor.
     *
     * @param LeadModel               $leadModel
     * @param CorePermissions         $permissions
     * @param DoNotContact            $doNotContact
     * @param FrequencyRuleRepository $frequencyRuleRepository
     */
    public function __construct(
        LeadModel $leadModel,
        CorePermissions $permissions,
        DoNotContact $doNotContact,
        FrequencyRuleRepository $frequencyRuleRepository
    ) {
        $this->leadModel               = $leadModel;
        $this->permissions             = $permissions;
        $this->doNotContact            = $doNotContact;
        $this->frequencyRuleRepository = $frequencyRuleRepository;
    }

    public function update(array $contactIds, array $subscribedChannels, array $params, $preferredChannel)
    {
        $contacts = $this->getContacts($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->canEdit($contact)) {
                continue;
            }

            $this->updateChannels($contact, $subscribedChannels);
            $this->updateFrequencyRules($contact, $params, $preferredChannel);
        }
    }

    /**
     * Update contact's channels.
     *
     * @param Lead $contact
     */
    private function updateChannels(Lead $contact, array $subscribedChannels)
    {
        $leadChannels = $this->leadModel->getContactChannels($contact);
        $allChannels  = $this->leadModel->getPreferenceChannels();

        foreach ($subscribedChannels as $subscribedChannel) {
            if (!array_key_exists($subscribedChannel, $leadChannels)) {
                $contactable = $this->doNotContact->isContactable($contact, $subscribedChannel);
                // Only resubscribe if the contact did not opt out themselves
                if ($contactable === DNC::UNSUBSCRIBED) {
                    $this->doNotContact->removeDncForContact($contact->getId(), $subscribedChannel);
                }
            }
        }

        $dncChannels = array_diff($allChannels, $subscribedChannels);
        if (!empty($dncChannels)) {
            foreach ($dncChannels as $channel) {
                $this->doNotContact->addDncForContact($contact->getId(), $channel, DNC::MANUAL, 'updated manually by user');
            }
        }
    }

    private function updateFrequencyRules(Lead $contact, array $params, $preferredChannel)
    {
        $frequencyRules = $contact->getFrequencyRules()->toArray();
        $channels       = $this->leadModel->getPreferenceChannels();

        foreach ($channels as $channel) {
            if (is_null($preferredChannel)) {
                $preferredChannel = $channel;
            }

            $frequencyRule = (isset($frequencyRules[$channel])) ? $frequencyRules[$channel] : new FrequencyRule();
            $frequencyRule->setChannel($channel);
            $frequencyRule->setLead($contact);
            $frequencyRule->setDateAdded(new \DateTime());

            if (!empty($params['frequency_number_'.$channel]) && !empty($params['frequency_time_'.$channel])) {
                $frequencyRule->setFrequencyNumber($params['frequency_number_'.$channel]);
                $frequencyRule->setFrequencyTime($params['frequency_time_'.$channel]);
            } else {
                $frequencyRule->setFrequencyNumber(null);
                $frequencyRule->setFrequencyTime(null);
            }

            if (!empty($params['contact_pause_start_date_'.$channel])) {
                $frequencyRule->setPauseFromDate(new \DateTime($params['contact_pause_start_date_'.$channel]));
            }

            if (!empty($params['contact_pause_end_date_'.$channel])) {
                $frequencyRule->setPauseToDate(new \DateTime($params['contact_pause_end_date_'.$channel]));
            }

            $frequencyRule->setLead($contact);
            $frequencyRule->setPreferredChannel($preferredChannel === $channel);

            $contact->addFrequencyRule($frequencyRule);

            $this->frequencyRuleRepository->saveEntity($frequencyRule);
        }
    }

    /**
     * @param Lead $contact
     *
     * @return bool
     */
    private function canEdit(Lead $contact)
    {
        return $this->permissions->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser());
    }

    /**
     * @param array $ids
     *
     * @return Paginator
     */
    private function getContacts(array $ids)
    {
        return $this->leadModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $ids,
                    ],
                ],
            ],
        ]);
    }
}
