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

use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class FrequencyActionModel
{
    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    /**
     * @param LeadModel               $contactModel
     * @param FrequencyRuleRepository $frequencyRuleRepository
     */
    public function __construct(
        LeadModel $contactModel,
        FrequencyRuleRepository $frequencyRuleRepository
    ) {
        $this->contactModel            = $contactModel;
        $this->frequencyRuleRepository = $frequencyRuleRepository;
    }

    /**
     * Update channels.
     *
     * @param array  $contactIds
     * @param array  $params
     * @param string $preferredChannel
     */
    public function update(array $contactIds, array $params, $preferredChannel)
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->updateFrequencyRules($contact, $params, $preferredChannel);
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

            $frequencyRule = isset($frequencyRules[$channel]) ? $frequencyRules[$channel] : new FrequencyRule();
            $frequencyRule->setChannel($channel);
            $frequencyRule->setLead($contact);

            if (!$frequencyRule->getDateAdded()) {
                $frequencyRule->setDateAdded(new \DateTime());
            }

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

            $frequencyRule->setPreferredChannel($preferredChannel === $channel);

            $contact->addFrequencyRule($frequencyRule);

            $this->frequencyRuleRepository->saveEntity($frequencyRule);
        }
    }
}
