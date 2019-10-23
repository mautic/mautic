<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMappingRepository;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UIContactIntegrationsTabSubscriber implements EventSubscriberInterface
{
    /**
     * @var ObjectMappingRepository
     */
    private $objectMappingRepository;

    /**
     * @param ObjectMappingRepository $objectMappingRepository
     */
    public function __construct(ObjectMappingRepository $objectMappingRepository)
    {
        $this->objectMappingRepository = $objectMappingRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 0],
        ];
    }

    /**
     * @param CustomTemplateEvent $event
     */
    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        if ('MauticLeadBundle:Lead:lead.html.php' === $event->getTemplate()) {
            $vars         = $event->getVars();
            $integrations = $vars['integrations'];

            /** @var Lead $contact */
            $contact = $vars['lead'];

            $objectMappings = $this->objectMappingRepository->getIntegrationMappingsForInternalObject(
                Contact::NAME,
                (int) $contact->getId()
            );

            foreach ($objectMappings as $objectMapping) {
                $integrations[] = [
                    'integration'           => $objectMapping->getIntegration(),
                    'integration_entity'    => $objectMapping->getIntegrationObjectName(),
                    'integration_entity_id' => $objectMapping->getIntegrationObjectId(),
                    'date_added'            => $objectMapping->getDateCreated(),
                    'last_sync_date'        => $objectMapping->getLastSyncDate(),
                ];
            }

            $vars['integrations'] = $integrations;

            $event->setVars($vars);
        }
    }
}
