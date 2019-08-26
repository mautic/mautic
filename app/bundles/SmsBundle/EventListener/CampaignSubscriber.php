<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\SmsEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var SmsModel
     */
    protected $smsModel;

    /**
     * @var TransportChain
     */
    protected $transportChain;

    /**
     * CampaignSubscriber constructor.
     *
     * @param SmsModel       $smsModel
     * @param TransportChain $transportChain
     */
    public function __construct(
        SmsModel $smsModel,
        TransportChain $transportChain
    ) {
        $this->smsModel          = $smsModel;
        $this->transportChain    = $transportChain;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD     => ['onCampaignBuild', 0],
            SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        if (count($this->transportChain->getEnabledTransports()) > 0) {
            $event->addAction(
                'sms.send_text_sms',
                [
                    'label'            => 'mautic.campaign.sms.send_text_sms',
                    'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
                    'eventName'        => SmsEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                    'formType'         => 'smssend_list',
                    'formTypeOptions'  => ['update_select' => 'campaignevent_properties_sms'],
                    'formTheme'        => 'MauticSmsBundle:FormTheme\SmsSendList',
                    'timelineTemplate' => 'MauticSmsBundle:SubscribedEvents\Timeline:index.html.php',
                    'channel'          => 'sms',
                    'channelIdField'   => 'sms',
                ]
            );
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     *
     * @return $this
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $lead  = $event->getLead();

        $smsId = (int) $event->getConfig()['sms'];
        $sms   = $this->smsModel->getEntity($smsId);

        if (!$sms) {
            return $event->setFailed('mautic.sms.campaign.failed.missing_entity');
        }

        $result = $this->smsModel->sendSms($sms, $lead, ['channel' => ['campaign.event', $event->getEvent()['id']]])[$lead->getId()];

        if ('Authenticate' === $result['status']) {
            // Don't fail the event but reschedule it for later
            return $event->setResult(false);
        }

        if (!empty($result['sent'])) {
            $event->setChannel('sms', $sms->getId());
            $event->setResult($result);
        } else {
            $result['failed'] = true;
            $result['reason'] = $result['status'];
            $event->setResult($result);
        }
    }
}
