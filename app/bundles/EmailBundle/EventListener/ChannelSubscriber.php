<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

const CHANNEL_COLUMN_CATEGORY_ID     = 'category_id';
const CHANNEL_COLUMN_NAME            = 'name';
const CHANNEL_COLUMN_DESCRIPTION     = 'description';
const CHANNEL_COLUMN_DATE_ADDED      = 'date_added';
const CHANNEL_COLUMN_CREATED_BY      = 'created_by';
const CHANNEL_COLUMN_CREATED_BY_USER = 'created_by_user';

class ChannelSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 100],
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
