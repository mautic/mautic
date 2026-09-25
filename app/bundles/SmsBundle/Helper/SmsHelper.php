<?php

namespace Mautic\SmsBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\DoNotContact as DoNotContactEntity;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\SmsBundle\Form\Type\ConfigType;

final readonly class SmsHelper
{
    public function __construct(
        private LeadRepository $leadRepository,
        private PhoneNumberHelper $phoneNumberHelper,
        private DoNotContact $doNotContact,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function unsubscribe($number): ?DoNotContactEntity
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

        if ($leads !== []) {
            $lead = array_shift($leads);
        } else {
            // Try to find the lead based on the given phone number
            $args['filter']['force'][0]['column'] = 'phone';

            $leads = $this->leadRepository->getEntities($args);

            if ($leads !== []) {
                $lead = array_shift($leads);
            } else {
                return null;
            }
        }

        return $this->doNotContact->addDncForContact($lead->getId(), 'sms', DoNotContactEntity::UNSUBSCRIBED);
    }

    public function getDisableTrackableUrls(): bool
    {
        return $this->coreParametersHelper->get(ConfigType::SMS_DISABLE_TRACKABLE_URLS);
    }
}
