<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\EventListener;

use MauticPlugin\IntegrationsBundle\Event\InternalObjectEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Internal\Object\Company;
use MauticPlugin\IntegrationsBundle\Internal\Object\Contact;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InternalObjectSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
        ];
    }

    /**
     * @param InternalObjectEvent $event
     */
    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Contact());
        $event->addObject(new Company());
    }
}
