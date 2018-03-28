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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeChannelsAction implements ActionInterface
{
    /**
     * @var int[]
     */
    private $leadsIds;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var array
     */
    private $subscribedChannels;

    /**
     * @var array
     */
    private $frequencyNumberEmail;

    /**
     * @var array
     */
    private $frequencyTimeEmail;

    /**
     * @var \DateTime
     */
    private $contactPauseStartDateEmail;

    /**
     * @var \DateTime
     */
    private $contactPauseEndDateEmail;

    /**
     * ChangeChannelsAction constructor.
     *
     * @param array     $leadsIds
     * @param LeadModel $leadModel
     * @param array     $subscribedChannels
     * @param array     $frequencyNumberEmail
     * @param array     $frequencyTimeEmail
     * @param \DateTime $contactPauseStartDateEmail
     * @param \DateTime $contactPauseEndDateEmail
     */
    public function __construct(
        array $leadsIds,
        LeadModel $leadModel,
        array $subscribedChannels,
        array $frequencyNumberEmail,
        array $frequencyTimeEmail,
        \DateTime $contactPauseStartDateEmail,
        \DateTime $contactPauseEndDateEmail
    ) {
        $this->leadsIds                   = $leadsIds;
        $this->leadModel                  = $leadModel;
        $this->subscribedChannels         = $subscribedChannels;
        $this->frequencyNumberEmail       = $frequencyNumberEmail;
        $this->frequencyTimeEmail         = $frequencyTimeEmail;
        $this->contactPauseStartDateEmail = $contactPauseStartDateEmail;
        $this->contactPauseEndDateEmail   = $contactPauseEndDateEmail;
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
        // TODO: update lead's channels
    }
}
