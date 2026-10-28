<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Model\ReportModel;

final class ChannelSubscriber
{
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ChannelEvent::class,
        priority: 100
    )]
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
