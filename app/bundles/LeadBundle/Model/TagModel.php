<?php

namespace Mautic\LeadBundle\Model;

use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Event\TagEvent;
use Mautic\LeadBundle\Event\TagMergeEvent;
use Mautic\LeadBundle\Form\Type\TagEntityType;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Tag>
 */
class TagModel extends FormModel
{
    /**
     * @var array<int, string>
     */
    private const TAG_PROPERTY_KEYS = [
        'add_tags',
        'remove_tags',
        'tags',
    ];

    /**
     * @return TagRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Tag::class);
    }

    public function getPermissionBase(): string
    {
        return 'lead:leads';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param int $id
     */
    public function getEntity($id = null): ?Tag
    {
        if (is_null($id)) {
            return new Tag();
        }

        return parent::getEntity($id);
    }

    /**
     * @param Tag   $entity
     * @param array $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Tag) {
            throw new MethodNotAllowedHttpException(['Tag']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TagEntityType::class, $entity, $options);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Tag) {
            throw new MethodNotAllowedHttpException(['Tag']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::TAG_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::TAG_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::TAG_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::TAG_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new TagEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    public function tagMerge(Tag $primaryTag, Tag $secondaryTag): Tag
    {
        $this->logger->debug('TAG: Merging tags');

        if ($primaryTag->getId() === $secondaryTag->getId()) {
            return $primaryTag;
        }

        $event = new TagMergeEvent($primaryTag, $secondaryTag);
        $this->em->beginTransaction();

        try {
            $this->dispatcher->dispatch($event, LeadEvents::TAG_PRE_MERGE);
            $this->replaceLeadTagAssociations($primaryTag, $secondaryTag);
            $this->replaceMergedTagReferences($primaryTag, $secondaryTag);
            $this->saveEntity($primaryTag, false);
            $this->deleteEntity($secondaryTag);
            $this->dispatcher->dispatch($event, LeadEvents::TAG_POST_MERGE);
            $this->em->commit();
        } catch (\Throwable $exception) {
            $this->em->rollback();

            throw $exception;
        }

        return $primaryTag;
    }

    private function replaceLeadTagAssociations(Tag $primaryTag, Tag $secondaryTag): void
    {
        $connection = $this->em->getConnection();

        $connection->executeStatement(
            sprintf('UPDATE IGNORE %slead_tags_xref SET tag_id = :primaryTagId WHERE tag_id = :secondaryTagId', MAUTIC_TABLE_PREFIX),
            [
                'primaryTagId'   => (int) $primaryTag->getId(),
                'secondaryTagId' => (int) $secondaryTag->getId(),
            ],
        );

        $connection->executeStatement(
            sprintf('DELETE FROM %slead_tags_xref WHERE tag_id = :secondaryTagId', MAUTIC_TABLE_PREFIX),
            ['secondaryTagId' => (int) $secondaryTag->getId()],
        );
    }

    private function replaceMergedTagReferences(Tag $primaryTag, Tag $secondaryTag): void
    {
        $primaryTagId   = (int) $primaryTag->getId();
        $secondaryTagId = (int) $secondaryTag->getId();

        $this->replaceTagPropertiesInEntities(
            $this->em->getRepository(CampaignEvent::class)->findBy(['type' => ['lead.changetags', 'lead.tags']]),
            $primaryTag,
            $secondaryTag,
        );

        $this->replaceTagPropertiesInEntities(
            $this->em->getRepository(Action::class)->findBy(['type' => 'lead.changetags']),
            $primaryTag,
            $secondaryTag,
        );

        $this->replaceTagPropertiesInEntities(
            $this->em->getRepository(TriggerEvent::class)->findBy(['type' => 'lead.changetags']),
            $primaryTag,
            $secondaryTag,
        );

        $this->replaceTagFiltersInSegments($primaryTagId, $secondaryTagId);
        $this->replaceTagFiltersInReports($primaryTagId, $secondaryTagId);
    }

    /**
     * @param iterable<CampaignEvent|Action|TriggerEvent> $entities
     */
    private function replaceTagPropertiesInEntities(iterable $entities, Tag $primaryTag, Tag $secondaryTag): void
    {
        foreach ($entities as $entity) {
            $properties = $entity->getProperties();
            $updated    = $this->replaceTagValuesInConfiguredProperties($properties, $secondaryTag->getTag(), $primaryTag->getTag());

            if ($entity instanceof CampaignEvent) {
                $updated = $this->replaceTagIdsInNestedProperties($updated, (int) $secondaryTag->getId(), (int) $primaryTag->getId());
            }

            if ($updated === $properties) {
                continue;
            }

            $entity->setProperties($updated);
            $this->em->persist($entity);
        }
    }

    private function replaceTagFiltersInSegments(int $primaryTagId, int $secondaryTagId): void
    {
        /** @var LeadList $segment */
        foreach ($this->em->getRepository(LeadList::class)->createQueryBuilder('l')
            ->where('l.filters LIKE :tagFilter')
            ->setParameter('tagFilter', '%"tags"%')
            ->getQuery()
            ->getResult() as $segment) {
            $filters = $segment->getFilters();
            $updated = $this->replaceTagValuesInSegmentFilters($filters, $secondaryTagId, $primaryTagId);

            if ($updated === $filters) {
                continue;
            }

            $segment->setFilters($updated);
            $this->em->persist($segment);
        }
    }

    private function replaceTagFiltersInReports(int $primaryTagId, int $secondaryTagId): void
    {
        /** @var Report $report */
        foreach ($this->em->getRepository(Report::class)->createQueryBuilder('r')
            ->where('r.filters LIKE :tagFilter')
            ->setParameter('tagFilter', '%tag"%')
            ->getQuery()
            ->getResult() as $report) {
            $filters = $report->getFilters();
            $updated = $this->replaceTagValuesInReportFilters($filters, $secondaryTagId, $primaryTagId);

            if ($updated === $filters) {
                continue;
            }

            $report->setFilters($updated);
            $this->em->persist($report);
        }
    }

    /**
     * @param array<string|int, mixed> $properties
     *
     * @return array<string|int, mixed>
     */
    private function replaceTagValuesInConfiguredProperties(array $properties, int|string $oldValue, int|string $newValue): array
    {
        foreach ($properties as $key => $value) {
            if ('properties' === $key) {
                continue;
            }

            if (is_array($value)) {
                $properties[$key] = in_array($key, self::TAG_PROPERTY_KEYS, true)
                    ? $this->replaceTagValues($value, $oldValue, $newValue)
                    : $this->replaceTagValuesInConfiguredProperties($value, $oldValue, $newValue);
            }
        }

        return $properties;
    }

    /**
     * @param array<string|int, mixed> $properties
     *
     * @return array<string|int, mixed>
     */
    private function replaceTagIdsInNestedProperties(array $properties, int $oldTagId, int $newTagId): array
    {
        if (!isset($properties['properties']) || !is_array($properties['properties'])) {
            return $properties;
        }

        foreach (self::TAG_PROPERTY_KEYS as $key) {
            if (!isset($properties['properties'][$key]) || !is_array($properties['properties'][$key])) {
                continue;
            }

            $properties['properties'][$key] = $this->replaceTagValues($properties['properties'][$key], $oldTagId, $newTagId);
        }

        return $properties;
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function replaceTagValuesInSegmentFilters(array $filters, int $oldTagId, int $newTagId): array
    {
        foreach ($filters as $index => $filter) {
            if ('tags' !== ($filter['type'] ?? null)) {
                continue;
            }

            if (isset($filter['properties']['filter']) && is_array($filter['properties']['filter'])) {
                $filters[$index]['properties']['filter'] = $this->replaceTagValues($filter['properties']['filter'], $oldTagId, $newTagId);
            }

            if (isset($filter['filter']) && is_array($filter['filter'])) {
                $filters[$index]['filter'] = $this->replaceTagValues($filter['filter'], $oldTagId, $newTagId);
            }
        }

        return $filters;
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function replaceTagValuesInReportFilters(array $filters, int $oldTagId, int $newTagId): array
    {
        foreach ($filters as $index => $filter) {
            if ('tag' !== ($filter['column'] ?? null)) {
                continue;
            }

            if (is_array($filter['value'] ?? null)) {
                $filters[$index]['value'] = $this->replaceTagValues($filter['value'], $oldTagId, $newTagId);
            }
        }

        return $filters;
    }

    /**
     * @param array<int, int|string> $values
     *
     * @return array<int, int|string>
     */
    private function replaceTagValues(array $values, int|string $oldValue, int|string $newValue): array
    {
        $updated = [];
        $seen    = [];

        foreach ($values as $value) {
            if ((string) $value === (string) $oldValue) {
                $value = $newValue;
            }

            $dedupeKey = (string) $value;
            if (isset($seen[$dedupeKey])) {
                continue;
            }

            $seen[$dedupeKey] = true;
            $updated[]        = $value;
        }

        return $updated;
    }
}
