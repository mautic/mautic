<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;

/**
 * Trait providing common import/export functionality for entity subscribers.
 */
trait ImportExportTrait
{
    /**
     * Common duplication check logic for entity import analysis.
     *
     * @template T of object
     *
     * @param EntityImportAnalyzeEvent $event         The import analyze event
     * @param string                   $entityName    The entity name to check
     * @param class-string<T>          $entityClass   The entity class to query
     * @param string                   $nameField     The field name to use for entity names (e.g., 'name', 'title')
     * @param EntityManagerInterface   $entityManager The entity manager
     */
    protected function performDuplicationCheck(
        EntityImportAnalyzeEvent $event,
        string $entityName,
        string $entityClass,
        string $nameField,
        EntityManagerInterface $entityManager,
    ): void {
        if ($entityName !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => []],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => []],
            'errors'                  => [],
        ];

        /** @var EntityRepository<T> $repository */
        $repository = $entityManager->getRepository($entityClass);

        foreach ($event->getEntityData() as $item) {
            if (!empty($item['uuid']) && !UuidTrait::isValidUuid($item['uuid'])) {
                $summary['errors'][] = sprintf('Invalid UUID format for %s', $event->getEntityName());
                break;
            }

            $existing = $repository->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][]   = $existing->{'get'.ucfirst($nameField)}();
                $summary[EntityImportEvent::UPDATE]['uuids'][]   = $existing->getUuid();
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item[$nameField] ?? 'Unnamed entity';
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
                $event->setSummary($type, [$entityName => $data]);
            }
        }
    }

    /**
     * Merge exported data avoiding duplicate entries.
     *
     * @param array<string, array<mixed>> $data
     */
    protected function mergeExportData(array &$data, EntityExportEvent $subEvent): void
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
}
