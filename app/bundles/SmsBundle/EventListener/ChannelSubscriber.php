<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\SmsBundle\Form\Type\SmsListType;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TransportChain $transportChain
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 90],
        ];
    }

    public function onAddChannel(ChannelEvent $event): void
    {
        if (count($this->transportChain->getEnabledTransports()) > 0) {
            $event->addChannel(
                'sms',
                [
                    MessageModel::CHANNEL_FEATURE => [
                        'campaignAction'             => 'sms.send_text_sms',
                        'campaignDecisionsSupported' => [
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType' => SmsListType::class,
                        'repository'     => \Mautic\SmsBundle\Entity\Sms::class,
                    ],
                    LeadModel::CHANNEL_FEATURE   => [],
                    ReportModel::CHANNEL_FEATURE => [
                        'table' => 'sms_messages',
                    ],
                ]
            );
        }
    }
}
