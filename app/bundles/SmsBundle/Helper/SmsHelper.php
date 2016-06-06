<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Helper;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\SmsEvents;

class SmsHelper
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function unsubscribe($number)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($number, 'US');
        $number = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

        /** @var \Mautic\LeadBundle\Entity\LeadRepository $repo */
        $repo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');

        $args = array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'mobile',
                        'expr' => 'eq',
                        'value' => $number
                    )
                )
            )
        );

        $leads = $repo->getEntities($args);

        if (! empty($leads)) {
            $lead = array_shift($leads);
        } else {
            // Try to find the lead based on the given phone number
            $args['filter']['force'][0]['column'] = 'phone';

            $leads = $repo->getEntities($args);

            if (! empty($leads)) {
                $lead = array_shift($leads);
            } else {
                return false;
            }
        }

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        return $leadModel->addDncForLead($lead, 'sms', null, DoNotContact::UNSUBSCRIBED);
    }

    /**
     * @param array $config
     * @param Lead $lead
     * @param MauticFactory $factory
     *
     * @return boolean
     */
    public static function send(array $config, Lead $lead, MauticFactory $factory)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $factory->getModel('lead.lead');

        if ($leadModel->isContactable($lead, 'sms') !== DoNotContact::IS_CONTACTABLE) {
            return array('failed' => 1);
        }

        $leadPhoneNumber = $lead->getFieldValue('mobile');

        if (empty($leadPhoneNumber)) {
            $leadPhoneNumber = $lead->getFieldValue('phone');
        }

        if (empty($leadPhoneNumber)) {
            return array('failed' => 1);
        }

        /** @var \Mautic\SmsBundle\Api\AbstractSmsApi $sms */
        $smsApi = $factory->getKernel()->getContainer()->get('mautic.sms.api');
        /** @var \Mautic\SmsBundle\Model\SmsModel $smsModel */
        $smsModel = $factory->getModel('sms');
        $smsId = (int) $config['sms'];
        /** @var \Mautic\SmsBundle\Entity\Sms $sms */
        $sms = $smsModel->getEntity($smsId);

        if ($sms->getId() !== $smsId) {
            return array('failed' => 1);
        }

        $dispatcher = $factory->getDispatcher();
        $event = new SmsSendEvent($sms->getMessage(), $lead);
        $event->setSmsId($smsId);

        try {
            $dispatcher->dispatch(SmsEvents::SMS_ON_SEND, $event);
        } catch (\Exception $exception) {
            // A listener has prevented this from being sent
            return array('failed' => 1);
        }

        $metadata = $smsApi->sendSms($leadPhoneNumber, $event->getContent());

        // If there was a problem sending at this point, it's an API problem and should be requeued
        if ($metadata === false) {
            return false;
        }

        return array(
            'type' => 'mautic.sms.sms',
            'status' => 'mautic.sms.timeline.status.delivered',
            'id' => $sms->getId(),
            'name' => $sms->getName(),
            'content' => $event->getContent()
        );
    }
}