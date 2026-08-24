<?php

namespace Mautic\SmsBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\DoNotContact as DoNotContactEntity;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Form\Type\ConfigType;
use Mautic\SmsBundle\Model\SmsModel;

class SmsHelper
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected LeadModel $leadModel,
        protected PhoneNumberHelper $phoneNumberHelper,
        protected SmsModel $smsModel,
        protected IntegrationHelper $integrationHelper,
        private readonly DoNotContact $doNotContact,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function unsubscribe($number): false|DoNotContactEntity
    {
        $number = $this->phoneNumberHelper->format($number);

        $args = [
            'filter' => [
                'force' => [
                    [
                        'column' => 'mobile',
                        'expr'   => 'eq',
                        'value'  => $number,
                    ],
                ],
            ],
        ];

        $leads = $this->leadRepository->getEntities($args);

        if (!empty($leads)) {
            $lead = array_shift($leads);
        } else {
            // Try to find the lead based on the given phone number
            $args['filter']['force'][0]['column'] = 'phone';

            $leads = $this->leadRepository->getEntities($args);

            if (!empty($leads)) {
                $lead = array_shift($leads);
            } else {
                return false;
            }
        }

        return $this->doNotContact->addDncForContact($lead->getId(), 'sms', DoNotContactEntity::UNSUBSCRIBED);
    }

    public function getDisableTrackableUrls(): bool
    {
        return $this->coreParametersHelper->get(ConfigType::SMS_DISABLE_TRACKABLE_URLS);
    }
}
