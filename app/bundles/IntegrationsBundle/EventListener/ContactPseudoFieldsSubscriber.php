<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\IntegrationsBundle\Event\InternalContactProcessPseudFieldsEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactPseudoFieldsSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContactObjectHelper
     */
    private $contactObjectHelper;

    /**
     * ContactPseudoFieldsSubscriber constructor.
     */
    public function __construct(ContactObjectHelper $contactObjectHelper)
    {
        $this->contactObjectHelper = $contactObjectHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationEvents::INTEGRATION_CONTACT_PROCESS_PSEUDO_FIELDS => ['processPseudoFields', 0],
        ];
    }

    public function processPseudoFields(InternalContactProcessPseudFieldsEvent $processPseudFieldsEvent)
    {
        $this->contactObjectHelper->processStandardPseudoFields(
            $processPseudFieldsEvent->getContact(),
            $processPseudFieldsEvent->getFields(),
            $processPseudFieldsEvent->getIntegration()
        );
    }
}
