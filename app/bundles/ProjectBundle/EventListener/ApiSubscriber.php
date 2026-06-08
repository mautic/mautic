<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\EventListener;

use Mautic\ApiBundle\Event\ApiInitializeEvent;
use Mautic\ApiBundle\Serializer\Exclusion\FieldInclusionStrategy;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\StageBundle\Entity\Stage;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ApiSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ApiInitializeEvent::class=> ['onApiInitializeEvent', 0],
        ];
    }

    public function onApiInitializeEvent(ApiInitializeEvent $event): void
    {
        if (!in_array($event->getEntityClass(), [
            Asset::class,
            Campaign::class,
            Message::class,
            DynamicContent::class,
            Email::class,
            Form::class,
            Company::class,
            LeadList::class,
            Page::class,
            Point::class,
            Trigger::class,
            Sms::class,
            Stage::class,
            Focus::class,
        ])) {
            return;
        }

        $event->addSerializerGroup('projectList');
        $event->addExclusionStrategy(new FieldInclusionStrategy(['id', 'name'], 1, 'projects'));
    }
}
