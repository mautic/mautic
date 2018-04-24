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
    private $contactModel;

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
     * @param LeadModel               $contactModel
     * @param DoNotContact            $doNotContact
     * @param FrequencyRuleRepository $frequencyRuleRepository
     */
    public function __construct(
        LeadModel $contactModel,
        DoNotContact $doNotContact,
        FrequencyRuleRepository $frequencyRuleRepository
    ) {
        $this->contactModel            = $contactModel;
        $this->doNotContact            = $doNotContact;
        $this->frequencyRuleRepository = $frequencyRuleRepository;
    }

    /**
     * Update channels and frequency rules.
     *
     * @param array  $contactIds
     * @param array  $subscribedChannels
     * @param array  $params
     * @param string $preferredChannel
     */
    public function update(array $contactIds, array $subscribedChannels, array $params, $preferredChannel)
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
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
        $leadChannels = $this->contactModel->getContactChannels($contact);
        $allChannels  = $this->contactModel->getPreferenceChannels();

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

    /**
     * @param Lead   $contact
     * @param array  $params
     * @param string $preferredChannel
     */
    private function updateFrequencyRules(Lead $contact, array $params, $preferredChannel)
    {
        $frequencyRules = $contact->getFrequencyRules()->toArray();
        $channels       = $this->contactModel->getPreferenceChannels();

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
}
