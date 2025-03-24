<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EntityImportUndoSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private AuditLogModel $auditLogModel;
    private IpLookupHelper $ipLookupHelper;

    // Mapping of key to corresponding entity and bundle information
    private array $entityMappings = [
        Campaign::ENTITY_NAME => [
            'entity' => Campaign::class,
            'bundle' => 'campaign',
            'object' => 'campaign',
        ],
        Form::ENTITY_NAME => [
            'entity' => Form::class,
            'bundle' => 'form',
            'object' => 'form',
        ],
        LeadList::ENTITY_NAME => [
            'entity' => LeadList::class,
            'bundle' => 'lead',
            'object' => 'list',
        ],
        Asset::ENTITY_NAME => [
            'entity' => Asset::class,
            'bundle' => 'asset',
            'object' => 'asset',
        ],
        Page::ENTITY_NAME => [
            'entity' => Page::class,
            'bundle' => 'page',
            'object' => 'page',
        ],
        LeadField::ENTITY_NAME => [
            'entity' => LeadField::class,
            'bundle' => 'lead',
            'object' => 'custom_field',
        ],
        Email::ENTITY_NAME => [
            'entity' => Email::class,
            'bundle' => 'email',
            'object' => 'email',
        ],
        DynamicContent::ENTITY_NAME => [
            'entity' => DynamicContent::class,
            'bundle' => 'dynamicContent',
            'object' => 'dynamicContent',
        ],
        Event::ENTITY_NAME => [
            'entity' => Event::class,
            'bundle' => 'campaign',
            'object' => 'event',
        ],
    ];

    public function __construct(EntityManagerInterface $entityManager, AuditLogModel $auditLogModel, IpLookupHelper $ipLookupHelper)
    {
        $this->entityManager  = $entityManager;
        $this->auditLogModel  = $auditLogModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityImportUndoEvent::class => 'onUndoImport',
        ];
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        $summary = $event->getSummary();

        foreach ($summary as $key => $data) {
            if (!isset($data['ids'])) {
                continue;
            }

            // If the key exists in the entityMappings, fetch the corresponding entity info
            if (isset($this->entityMappings[$key])) {
                $entityInfo = $this->entityMappings[$key];

                foreach ($data['ids'] as $id) {
                    $entity = $this->entityManager->getRepository($entityInfo['entity'])->find($id);

                    if ($entity) {
                        if (Event::ENTITY_NAME === $key) {
                            $this->cleanupEventDependentRecords($id);
                        }
                        $this->entityManager->remove($entity);
                        // Log the deletion
                        $log = [
                            'bundle'    => $entityInfo['bundle'],
                            'object'    => $entityInfo['object'],
                            'objectId'  => $id,
                            'action'    => 'undo_import',
                            'details'   => ['deletedEntity' => get_class($entity)],
                            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                        ];

                        $this->auditLogModel->writeToLog($log);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }

    private function cleanupEventDependentRecords(int $id): void
    {
        // Fetch dependent Event records where the parent_id refers to the entity being deleted
        $dependentEvents = $this->entityManager->getRepository(Event::class)->findBy(['parent' => $id]);

        foreach ($dependentEvents as $dependentEvent) {
            // Set parent_id to null
            $dependentEvent->setParent(null);
            $this->entityManager->persist($dependentEvent);
        }

        // Make sure changes are saved
        $this->entityManager->flush();
    }
}
