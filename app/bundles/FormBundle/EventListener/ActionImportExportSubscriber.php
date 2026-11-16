<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Helper\EventEntityReferences;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UuidHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Serializer\ImportEntityDenormalizer;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\ActionRepository;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Model\ActionModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ActionImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    /**
     * The actions being exported, as an action can lead back to itself, like through the unsubscribe form of the email it sends.
     *
     * @var array<int, true>
     */
    private array $exporting = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionRepository $actionRepository,
        private FormRepository $formRepository,
        private ActionModel $actionModel,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private EventDispatcherInterface $dispatcher,
        private ImportEntityDenormalizer $serializer,
        private EventEntityReferences $eventEntityReferences,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onExport', 0],
            EntityImportEvent::class        => ['onImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onExport(EntityExportEvent $event): void
    {
        if (Action::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $actionId = $event->getEntityId();
        $action   = $this->actionModel->getEntity($actionId);

        if (!$action instanceof Action || isset($this->exporting[$actionId])) {
            return;
        }

        $this->exporting[$actionId] = true;

        try {
            $this->exportAction($event, $action);
        } finally {
            unset($this->exporting[$actionId]);
        }
    }

    private function exportAction(EntityExportEvent $event, Action $action): void
    {
        $actionId = (int) $action->getId();

        $actionData = [
            'id'           => $action->getId(),
            'uuid'         => $action->getUuid(),
            'name'         => $action->getName(),
            'description'  => $action->getDescription(),
            'type'         => $action->getType(),
            'action_order' => $action->getOrder(),
            'properties'   => $action->getProperties(),
            'form'         => $action->getForm()->getId(),
        ];

        if ($linkedUuids = $this->getLinkedUuids($action)) {
            $actionData['linked_uuids'] = $linkedUuids;
        }

        $event->addEntity(Action::ENTITY_NAME, $actionData);
        $this->logAction('export', $actionId, $actionData, 'formAction');

        $data = [];

        // Takes along the emails, segments and other entities the action uses, so the import can point the action to them
        foreach ($this->eventEntityReferences->getFormActionExportedIds((string) $action->getType(), (array) $action->getProperties()) as $entityName => $entityIds) {
            foreach ($entityIds as $entityId) {
                $subEvent = new EntityExportEvent($entityName, $entityId);
                $this->dispatcher->dispatch($subEvent);
                $this->mergeExportData($data, $subEvent);
                $event->addDependencies($subEvent->getDependencies());

                $event->addDependencyEntity(Action::ENTITY_NAME, [
                    Action::ENTITY_NAME => $actionId,
                    $entityName         => $entityId,
                ]);
            }
        }
        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }
    }

    /**
     * The UUIDs of the entities the action refers to that are not exported, like an asset category, so the import can find them.
     *
     * @return array<string, array<int, string>>
     */
    private function getLinkedUuids(Action $action): array
    {
        $uuids = [];

        foreach ($this->eventEntityReferences->getFormActionLinkedIds((string) $action->getType(), (array) $action->getProperties()) as $kind => $ids) {
            foreach ($ids as $id) {
                $entity = $this->entityManager->find(EventEntityReferences::LINKED[$kind], $id);

                if ($entity instanceof UuidInterface && $entity->getUuid()) {
                    $uuids[$kind][$id] = $entity->getUuid();
                }
            }
        }

        return $uuids;
    }

    public function onImport(EntityImportEvent $event): void
    {
        if (Action::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $actionData) {
            $action = $this->actionRepository->findOneBy(['uuid' => $actionData['uuid']]);
            $isNew  = !$action;
            $action ??= new Action();

            if (!empty($actionData['form'])) {
                $form = $this->formRepository->find($actionData['form']);
                if ($form instanceof Form) {
                    $action->setForm($form);
                    unset($actionData['form']);
                }
            }

            if (isset($actionData['properties']) && is_string($actionData['properties'])) {
                $decoded                  = json_decode($actionData['properties'], true);
                $actionData['properties'] = is_array($decoded) ? $decoded : [];
            }

            $this->serializer->denormalize(
                $actionData,
                Action::class,
                null,
                ['object_to_populate' => $action]
            );

            $this->actionModel->saveEntity($action);
            $event->addEntityIdMap((int) $actionData['id'], $action->getId());

            $status                      = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][]   = $action->getName();
            $stats[$status]['ids'][]     = $action->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $action->getId(), $actionData, 'formAction');
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Action::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Action::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }

        foreach ($summary['ids'] as $id) {
            $action = $this->actionRepository->find($id);
            if ($action) {
                $this->entityManager->remove($action);
                $this->logAction('undo_import', $id, ['deletedEntity' => Action::class], 'formAction');
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (Action::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => []],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => []],
            'errors'                  => [],
        ];

        foreach ($event->getEntityData() as $item) {
            if (!empty($item['uuid']) && !UuidHelper::isValidUuid($item['uuid'])) {
                $summary['errors'][] = sprintf('Invalid UUID format for %s', $event->getEntityName());
                break;
            }

            $existing = $this->actionRepository->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][] = $existing->getName();
                $summary[EntityImportEvent::UPDATE]['uuids'][] = $existing->getUuid();
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['name'] ?? 'Unnamed action';
            }
        }

        foreach ($summary as $type => $data) {
            if ('errors' === $type) {
                if (count($data) > 0) {
                    $event->setSummary('errors', ['messages' => $data]);
                }
                break;
            }

            if (isset($data['names']) && count($data['names']) > 0) {
                $event->setSummary($type, [Action::ENTITY_NAME => $data]);
            }
        }
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details, string $object): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'form',
            'object'    => $object,
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
