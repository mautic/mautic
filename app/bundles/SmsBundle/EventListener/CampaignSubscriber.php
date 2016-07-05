<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\SmsBundle\Api\AbstractSmsApi;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\SmsEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticSmsBundle
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var SmsModel
     */
    protected $smsModel;

    /**
     * @var AbstractSmsApi
     */
    protected $smsApi;

    /**
     * CampaignSubscriber constructor.
     *
     * @param MauticFactory $factory
     * @param LeadModel $leadModel
     * @param SmsModel $smsModel
     * @param AbstractSmsApi $smsApi
     */
    public function __construct(MauticFactory $factory, LeadModel $leadModel, SmsModel $smsModel, AbstractSmsApi $smsApi)
    {
        $this->leadModel = $leadModel;
        $this->smsModel  = $smsModel;
        $this->smsApi    = $smsApi;

        parent::__construct($factory);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0]
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        if ($this->factory->getParameter('sms_enabled')) {
            $event->addAction(
                'sms.send_text_sms',
                [
                    'label'            => 'mautic.campaign.sms.send_text_sms',
                    'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
                    'eventName'        => SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                    'formType'         => 'smssend_list',
                    'formTypeOptions'  => ['update_select' => 'campaignevent_properties_sms'],
                    'formTheme'        => 'MauticSmsBundle:FormTheme\SmsSendList',
                    'timelineTemplate' => 'MauticSmsBundle:SubscribedEvents\Timeline:index.html.php'
                ]
            );
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $lead = $event->getLead();

        if ($this->leadModel->isContactable($lead, 'sms') !== DoNotContact::IS_CONTACTABLE) {
            return $event->setFailed('mautic.sms.campaign.failed.not_contactable');
        }

        $leadPhoneNumber = $lead->getFieldValue('mobile');

        if (empty($leadPhoneNumber)) {
            $leadPhoneNumber = $lead->getFieldValue('phone');
        }

        if (empty($leadPhoneNumber)) {
            return $event->setFailed('mautic.sms.campaign.failed.missing_number');
        }

        $smsId = (int) $event->getConfig()['sms'];
        $sms   = $this->smsModel->getEntity($smsId);

        if ($sms->getId() !== $smsId) {
            return $event->setFailed('mautic.sms.campaign.failed.missing_entity');
        }

        $smsEvent = new SmsSendEvent($sms->getMessage(), $lead);
        $smsEvent->setSmsId($smsId);

        $this->dispatcher->dispatch(SmsEvents::SMS_ON_SEND, $smsEvent);
        $metadata = $this->smsApi->sendSms($leadPhoneNumber, $smsEvent->getContent());

        // If there was a problem sending at this point, it's an API problem and should be requeued
        if ($metadata === false) {
            return $event->setResult(false);
        }

        $this->smsModel->getRepository()->upCount($smsId);
        
        $event->setResult(
            [
                'type'    => 'mautic.sms.sms',
                'status'  => 'mautic.sms.timeline.status.delivered',
                'id'      => $sms->getId(),
                'name'    => $sms->getName(),
                'content' => $smsEvent->getContent()
            ]
        );
    }
}