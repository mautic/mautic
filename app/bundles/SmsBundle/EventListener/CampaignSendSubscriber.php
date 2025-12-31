<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Form\Type\SmsSendType;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignSendSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SmsModel $smsModel,
        private TransportChain $transportChain,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD           => ['onCampaignBuild', 0],
            SmsEvents::ON_CAMPAIGN_TRIGGER_BATCH_ACTION => ['onCampaignTriggerBatchAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        if (count($this->transportChain->getEnabledTransports()) > 0) {
            $event->addAction(
                'sms.send_text_sms',
                [
                    'label'            => 'mautic.campaign.sms.send_text_sms',
                    'description'      => 'mautic.campaign.sms.send_text_sms.tooltip',
                    'batchEventName'   => SmsEvents::ON_CAMPAIGN_TRIGGER_BATCH_ACTION,
                    'formType'         => SmsSendType::class,
                    'formTypeOptions'  => ['update_select' => 'campaignevent_properties_sms'],
                    'formTheme'        => '@MauticSms/FormTheme/SmsSendList/smssend_list_row.html.twig',
                    'channel'          => 'sms',
                    'channelIdField'   => 'sms',
                ]
            );
        }
    }

    public function onCampaignTriggerBatchAction(PendingEvent $event): void
    {
        $smsId = (int) $event->getEvent()->getProperties()['sms'];
        $sms   = $smsId ? $this->smsModel->getEntity($smsId) : null;

        if (!$sms) {
            $event->passAllWithError($this->translator->trans('mautic.sms.campaign.failed.missing_entity'));

            return;
        }

        if (!$sms->isPublished()) {
            $event->passAllWithError($this->translator->trans('mautic.sms.campaign.failed.unpublished'));

            return;
        }

        $event->setChannel('sms', $sms->getId());
        $this->sendSmsInBatches($sms, $event);
    }

    private function sendSmsInBatches(Sms $sms, PendingEvent $event): void
    {
        $contacts = $event->getContacts();
        $this->smsModel->sendSMS($sms, $contacts, ['channel' => ['campaign.event', $event->getEvent()->getId()]]);
    }
}
