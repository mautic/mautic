<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Lead\ChangeChannelsAction;

use Mautic\CoreBundle\Batches\ActionInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeChannelsAction implements ActionInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    /**
     * @var int[]
     */
    private $leadsIds;

    /**
     * @var array
     */
    private $subscribedChannels;

    /**
     * @var string
     */
    private $preferredChannel;

    /**
     * @var array
     */
    private $requestParameters;

    /**
     * ChangeChannelsAction constructor.
     *
     * @param array                   $leadsIds
     * @param array                   $subscribedChannels
     * @param array                   $requestParameters
     * @param LeadModel               $leadModel
     * @param CorePermissions         $corePermissions
     * @param DoNotContact            $doNotContact
     * @param FrequencyRuleRepository $frequencyRuleRepository
     * @param string                  $preferredChannel
     */
    public function __construct(
        array $leadsIds,
        array $subscribedChannels,
        array $requestParameters,
        LeadModel $leadModel,
        CorePermissions $corePermissions,
        DoNotContact $doNotContact,
        FrequencyRuleRepository $frequencyRuleRepository,
        $preferredChannel
    ) {
        $this->leadsIds                   = $leadsIds;
        $this->subscribedChannels         = $subscribedChannels;
        $this->preferredChannel           = $preferredChannel;
        $this->leadModel                  = $leadModel;
        $this->corePermissions            = $corePermissions;
        $this->doNotContact               = $doNotContact;
        $this->requestParameters          = $requestParameters;
        $this->frequencyRuleRepository    = $frequencyRuleRepository;
    }

    /**
     * @see ActionInterface::execute()
     * {@inheritdoc}
     */
    public function execute()
    {
        $leads = $this->leadModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $this->leadsIds,
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ]);

        foreach ($leads as $lead) {
            $this->updateChannels($lead);
        }
    }

    /**
     * Update lead's channels.
     *
     * @param Lead $lead
     */
    private function updateChannels(Lead $lead)
    {
        $leadChannels = $this->leadModel->getContactChannels($lead);
        $allChannels  = $this->leadModel->getPreferenceChannels();

        foreach ($this->subscribedChannels as $subscribedChannel) {
            if (!array_key_exists($subscribedChannel, $leadChannels)) {
                $contactable = $this->doNotContact->isContactable($lead, $subscribedChannel);
                if ($contactable == DNC::UNSUBSCRIBED) {
                    // Only resubscribe if the contact did not opt out themselves
                    $this->doNotContact->removeDncForContact($lead->getId(), $subscribedChannel);
                }
            }
        }

        $dncChannels = array_diff($allChannels, $this->subscribedChannels);
        if (!empty($dncChannels)) {
            foreach ($dncChannels as $channel) {
                $this->doNotContact->addDncForContact($lead->getId(), $channel, 'user', DNC::UNSUBSCRIBED);
            }
        }

        $this->updateFrequencyRules($lead);
    }

    private function updateFrequencyRules(Lead $lead)
    {
        $frequencyRules = $lead->getFrequencyRules()->toArray();
        $entities       = [];
        $channels       = $this->leadModel->getPreferenceChannels();

        foreach ($channels as $ch) {
            if (is_null($this->preferredChannel)) {
                $this->preferredChannel = $ch;
            }

            $frequencyRule = (isset($frequencyRules[$ch])) ? $frequencyRules[$ch] : new FrequencyRule();
            $frequencyRule->setChannel($ch);
            $frequencyRule->setLead($lead);
            $frequencyRule->setDateAdded(new \DateTime());

            if (!empty($this->requestParameters['frequency_number_'.$ch]) && !empty($this->requestParameters['frequency_time_'.$ch])) {
                $frequencyRule->setFrequencyNumber($this->requestParameters['frequency_number_'.$ch]);
                $frequencyRule->setFrequencyTime($this->requestParameters['frequency_time_'.$ch]);
            } else {
                $frequencyRule->setFrequencyNumber(null);
                $frequencyRule->setFrequencyTime(null);
            }

            $frequencyRule->setPauseFromDate(!empty($this->requestParameters['contact_pause_start_date_'.$ch]) ? $this->requestParameters['contact_pause_start_date_'.$ch] : null);
            $frequencyRule->setPauseToDate(!empty($this->requestParameters['contact_pause_end_date_'.$ch]) ? $this->requestParameters['contact_pause_end_date_'.$ch] : null);

            $frequencyRule->setLead($lead);
            $frequencyRule->setPreferredChannel($this->preferredChannel === $ch);

            $lead->addFrequencyRule($frequencyRule);
        }

        if (!empty($entities)) {
            $this->frequencyRuleRepository->saveEntities($entities);
        }
    }
}
