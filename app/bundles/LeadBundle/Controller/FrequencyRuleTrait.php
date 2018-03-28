<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\Form;

trait FrequencyRuleTrait
{
    /**
     * @var
     */
    protected $leadLists;

    /**
     * @var
     */
    protected $dncChannels;

    /**
     * @var bool
     */
    protected $isPublicView = false;

    /**
     * @param      $lead
     * @param      $viewParameters
     * @param null $data
     * @param bool $isPublic
     * @param null $action
     *
     * @return bool|Form
     */
    protected function getFrequencyRuleForm($lead, &$viewParameters = [], &$data = null, $isPublic = false, $action = null)
    {
        /** @var LeadModel $model */
        $model = $this->getModel('lead');

        $leadChannels = $model->getContactChannels($lead);
        $allChannels  = $model->getPreferenceChannels();
        $leadLists    = $model->getLists($lead, true, true, $isPublic);

        $viewParameters = array_merge(
            $viewParameters,
            [
                'leadsLists'   => $leadLists,
                'channels'     => $allChannels,
                'leadChannels' => $leadChannels,
            ]
        );

        //find the email
        $currentChannelId = null;
        if (!empty($viewParameters['idHash'])) {
            $emailModel = $this->getModel('email');
            if ($stat = $emailModel->getEmailStatus($viewParameters['idHash'])) {
                if ($email = $stat->getEmail()) {
                    $currentChannelId = $email->getId();
                }
            }
        }

        if (null == $data) {
            $data = $this->getFrequencyRuleFormData($lead, $allChannels, $leadChannels, $isPublic);
        }

        /** @var Form $form */
        $form = $this->get('form.factory')->create(
            'lead_contact_frequency_rules',
            $data,
            [
                'action'             => $action,
                'channels'           => $allChannels,
                'public_view'        => $isPublic,
                'allow_extra_fields' => true,
            ]
        );

        $method = $this->request->getMethod();
        if ('GET' !== $method) {
            if (!$this->isFormCancelled($form)) {
                if ($this->isFormValid($form, $data)) {
                    $this->persistFrequencyRuleFormData($lead, $form->getData(), $allChannels, $leadChannels, $currentChannelId);

                    return true;
                }
            }
        }

        return $form;
    }

    /**
     * @param Lead       $lead
     * @param array|null $allChannels
     * @param null       $leadChannels
     * @param bool       $isPublic
     * @param null       $frequencyRules
     *
     * @return array
     */
    protected function getFrequencyRuleFormData(Lead $lead, array $allChannels = null, $leadChannels = null, $isPublic = false, $frequencyRules = null)
    {
        $data = [];

        /** @var LeadModel $model */
        $model = $this->getModel('lead');
        if (null === $allChannels) {
            $allChannels = $model->getPreferenceChannels();
        }

        if (null === $leadChannels) {
            $leadChannels = $model->getContactChannels($lead);
        }

        if (null === $frequencyRules) {
            $frequencyRules = $model->getFrequencyRules($lead);
        }

        foreach ($allChannels as $channel) {
            if (isset($frequencyRules[$channel])) {
                $frequencyRule                                       = $frequencyRules[$channel];
                $data['lead_channels']['frequency_number_'.$channel] = $frequencyRule['frequency_number'];
                $data['lead_channels']['frequency_time_'.$channel]   = $frequencyRule['frequency_time'];
                if ($frequencyRule['pause_from_date']) {
                    $data['lead_channels']['contact_pause_start_date_'.$channel] = new \DateTime($frequencyRule['pause_from_date']);
                }

                if ($frequencyRule['pause_to_date']) {
                    $data['lead_channels']['contact_pause_end_date_'.$channel] = new \DateTime($frequencyRule['pause_to_date']);
                }

                if (!empty($frequencyRule['preferred_channel'])) {
                    $data['lead_channels']['preferred_channel'] = $channel;
                }
            }
        }

        $data['global_categories'] = (isset($frequencyRules['global_categories']))
            ? $frequencyRules['global_categories']
            : $model->getLeadCategories(
                $lead
            );
        $this->leadLists    = $model->getLists($lead, false, false, $isPublic);
        $data['lead_lists'] = [];
        foreach ($this->leadLists as $leadList) {
            $data['lead_lists'][] = $leadList->getId();
        }

        $data['lead_channels']['subscribed_channels'] = $leadChannels;
        $this->isPublicView                           = $isPublic;

        return $data;
    }

    /**
     * @param Lead  $lead
     * @param array $formData
     * @param array $allChannels
     * @param       $leadChannels
     * @param int   $currentChannelId
     */
    protected function persistFrequencyRuleFormData(Lead $lead, array $formData, array $allChannels, $leadChannels, $currentChannelId = null)
    {
        /** @var LeadModel $model */
        $model = $this->getModel('lead');

        foreach ($formData['lead_channels']['subscribed_channels'] as $contactChannel) {
            if (!isset($leadChannels[$contactChannel])) {
                $contactable = $model->isContactable($lead, $contactChannel);
                if ($contactable == DoNotContact::UNSUBSCRIBED) {
                    // Only resubscribe if the contact did not opt out themselves
                    $model->removeDncForLead($lead, $contactChannel);
                }
            }
        }

        $dncChannels = array_diff($allChannels, $formData['lead_channels']['subscribed_channels']);
        if (!empty($dncChannels)) {
            foreach ($dncChannels as $channel) {
                if ($currentChannelId) {
                    $channel = [$channel => $currentChannelId];
                }
                $model->addDncForLead($lead, $channel, 'user', ($this->isPublicView) ? DoNotContact::UNSUBSCRIBED : DoNotContact::MANUAL);
            }
        }

        $model->setFrequencyRules($lead, $formData, $this->leadLists);
    }
}
