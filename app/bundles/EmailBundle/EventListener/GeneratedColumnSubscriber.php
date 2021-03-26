<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $sentDate = new GeneratedColumn('email_stats', 'generated_sent_date', 'DATE', "CONCAT(YEAR(date_sent), '-', LPAD(MONTH(date_sent), 2, '0'), '-', LPAD(DAY(date_sent), 2, '0'))");
        $sentDate->addIndexColumn('email_id');
        $sentDate->setOriginalDateColumn('date_sent', 'd');

        $event->addGeneratedColumn($sentDate);
    }
}
