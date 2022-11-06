<?php

namespace Mautic\SmsBundle\Helper;

use Doctrine\ORM\EntityManager;
use libphonenumber\PhoneNumberFormat;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\DoNotContact as DoNotContactEntity;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Form\Type\ConfigType;
use Mautic\SmsBundle\Model\SmsModel;

class SmsHelper
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var PhoneNumberHelper
     */
    protected $phoneNumberHelper;

    /**
     * @var SmsModel
     */
    protected $smsModel;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    private CoreParametersHelper $coreParametersHelper;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    public function __construct(
        EntityManager $em,
        LeadModel $leadModel,
        PhoneNumberHelper $phoneNumberHelper,
        SmsModel $smsModel,
        IntegrationHelper $integrationHelper,
        DoNotContact $doNotContact,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->em                   = $em;
        $this->leadModel            = $leadModel;
        $this->phoneNumberHelper    = $phoneNumberHelper;
        $this->smsModel             = $smsModel;
        $this->integrationHelper    = $integrationHelper;
        $this->doNotContact         = $doNotContact;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function unsubscribe($number)
    {
        $number = $this->phoneNumberHelper->format($number, PhoneNumberFormat::E164);

        /** @var LeadRepository $repo */
        $repo = $this->em->getRepository('MauticLeadBundle:Lead');

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

        $leads = $repo->getEntities($args);

        if (!empty($leads)) {
            $lead = array_shift($leads);
        } else {
            // Try to find the lead based on the given phone number
            $args['filter']['force'][0]['column'] = 'phone';

            $leads = $repo->getEntities($args);

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
