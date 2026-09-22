<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ChannelSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvent::class => ['onAddChannel', 100],
        ];
    }

    public function onAddChannel(ChannelEvent $event): void
    {
        $event->addChannel(
            'email',
            [
                MessageModel::CHANNEL_FEATURE => [
                    'campaignAction'             => 'email.send',
                    'campaignDecisionsSupported' => [
                        'email.open',
                        'page.pagehit',
                        'asset.download',
                        'form.submit',
                    ],
                    'lookupFormType' => EmailListType::class,
                ],
                LeadModel::CHANNEL_FEATURE   => [],
                ReportModel::CHANNEL_FEATURE => [
                    'table' => 'emails',
                ],
            ]
        );
    }
}
