<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\ActionModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ActionImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionModel $actionModel,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private EventDispatcherInterface $dispatcher,
        private DenormalizerInterface $serializer,
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

        if (!$action instanceof Action) {
            return;
        }

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

        $event->addEntity(Action::ENTITY_NAME, $actionData);
        $this->logAction('export', $actionId, $actionData, 'formAction');

        $data = [];

        if (
            isset($actionData['properties']['asset'])
            && !empty($actionData['properties']['asset'])
        ) {
            $assetId  = (int) $actionData['properties']['asset'];
            $subEvent = new EntityExportEvent(Asset::ENTITY_NAME, $assetId);
            $this->dispatcher->dispatch($subEvent);
            $this->mergeExportData($data, $subEvent);

            $event->addDependencyEntity(Action::ENTITY_NAME, [
                Action::ENTITY_NAME => $actionId,
                Asset::ENTITY_NAME  => $assetId,
            ]);
        }
        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }
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
            $action = $this->entityManager->getRepository(Action::class)->findOneBy(['uuid' => $actionData['uuid']]);
            $isNew  = !$action;
            $action ??= new Action();

            if (!empty($actionData['form'])) {
                $form = $this->entityManager->getRepository(Form::class)->find($actionData['form']);
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
            $action = $this->entityManager->getRepository(Action::class)->find($id);
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
            if (!empty($item['uuid']) && !UuidTrait::isValidUuid($item['uuid'])) {
                $summary['errors'][] = sprintf('Invalid UUID format for %s', $event->getEntityName());
                break;
            }

            $existing = $this->entityManager->getRepository(Action::class)->findOneBy(['uuid' => $item['uuid']]);
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
