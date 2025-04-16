<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FormImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormModel $formModel,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private EventDispatcherInterface $dispatcher,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onFormExport', 0],
            EntityImportEvent::class        => ['onFormImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onFormExport(EntityExportEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $formId       = $event->getEntityId();

        $form = $this->formModel->getEntity($formId);
        if (!$form) {
            return;
        }

        $formData = [
            'id'                   => $formId,
            'name'                 => $form->getName(),
            'is_published'         => $form->isPublished(),
            'description'          => $form->getDescription(),
            'alias'                => $form->getAlias(),
            'lang'                 => $form->getLanguage(),
            'cached_html'          => $form->getCachedHtml(),
            'post_action'          => $form->getPostAction(),
            'template'             => $form->getTemplate(),
            'form_type'            => $form->getFormType(),
            'render_style'         => $form->getRenderStyle(),
            'post_action_property' => $form->getPostActionProperty(),
            'form_attr'            => $form->getFormAttributes(),
            'uuid'                 => $form->getUuid(),
        ];
        $event->addEntity(Form::ENTITY_NAME, $formData);
        $this->logAction('export', $formId, $formData, 'form');

        $data           = [];

        foreach ($form->getFields() as $field) {
            $subEvent = new EntityExportEvent(Field::ENTITY_NAME, (int) $field->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addDependencies($subEvent->getDependencies());
            $this->mergeExportData($data, $subEvent);

            $event->addDependencyEntity(Form::ENTITY_NAME, [
                Form::ENTITY_NAME       => (int) $formId,
                Field::ENTITY_NAME      => (int) $field->getId(),
            ]);
        }

        foreach ($form->getActions() as $action) {
            $subEvent = new EntityExportEvent(Action::ENTITY_NAME, (int) $action->getId());
            $this->dispatcher->dispatch($subEvent);
            $event->addDependencies($subEvent->getDependencies());
            $this->mergeExportData($data, $subEvent);

            $event->addDependencyEntity(Form::ENTITY_NAME, [
                Form::ENTITY_NAME       => (int) $formId,
                Action::ENTITY_NAME     => (int) $action->getId(),
            ]);
        }

        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }
    }

    /**
     * Merge exported data avoiding duplicate entries.
     *
     * @param array<string, array<mixed>> $data
     */
    private function mergeExportData(array &$data, EntityExportEvent $subEvent): void
    {
        foreach ($subEvent->getEntities() as $key => $values) {
            if (!isset($data[$key])) {
                $data[$key] = $values;
            } else {
                $existingIds = array_column($data[$key], 'id');
                $data[$key]  = array_merge($data[$key], array_filter($values, function ($value) use ($existingIds) {
                    return !in_array($value['id'], $existingIds);
                }));
            }
        }
    }

    public function onFormImport(EntityImportEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $formData) {
            $form  = $this->entityManager->getRepository(Form::class)->findOneBy(['uuid' => $formData['uuid']]);
            $isNew = !$form;

            $form ??= new Form();
            $this->serializer->denormalize(
                $formData,
                Form::class,
                null,
                ['object_to_populate' => $form]
            );
            $this->formModel->saveEntity($form);

            $event->addEntityIdMap((int) $formData['id'], $form->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $form->getName();
            $stats[$status]['ids'][]   = $form->getId();
            ++$stats[$status]['count'];
            $this->logAction('import', $form->getId(), $formData, 'form');
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Form::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Form::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Form::class], 'form');
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => []],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => []],
        ];

        foreach ($event->getEntityData() as $item) {
            $existing = $this->entityManager->getRepository(Form::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][]   = $existing->getName();
                $summary[EntityImportEvent::UPDATE]['uuids'][]   = $existing->getUuid();
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['name'];
            }
        }

        foreach ($summary as $type => $data) {
            if (count($data['names']) > 0) {
                $event->setSummary($type, [Form::ENTITY_NAME => $data]);
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
