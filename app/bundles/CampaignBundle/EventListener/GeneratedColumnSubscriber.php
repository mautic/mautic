<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string,array<int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $dateDiff = new GeneratedColumn(
            'page_hits',
            'generated_date_diff',
            'INT(11)',
            'timestampdiff(SECOND,`date_hit`,`date_left`)'
        );

        $event->addGeneratedColumn($dateDiff);
    }

}
