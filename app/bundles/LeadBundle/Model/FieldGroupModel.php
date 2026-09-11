<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\FieldGroupRepository;
use Mautic\LeadBundle\Event\FieldGroupListEvent;
use Mautic\LeadBundle\Form\Type\FieldGroupType;
use Mautic\LeadBundle\Helper\FieldGroupAliasHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<FieldGroup>
 */
final class FieldGroupModel extends FormModel
{
    private FieldGroupAliasHelper $aliasHelper;

    private FieldGroupRepository $fieldGroupRepository;

    #[Required]
    public function setAliasHelper(
        FieldGroupAliasHelper $aliasHelper,
    ): void {
        $this->aliasHelper = $aliasHelper;
    }

    #[Required]
    public function autowireFieldGroupModel(
        FieldGroupRepository $fieldGroupRepository,
    ): void {
        $this->fieldGroupRepository = $fieldGroupRepository;
    }

    public function getRepository(): FieldGroupRepository
    {
        return $this->fieldGroupRepository;
    }

    public function getPermissionBase(): string
    {
        return 'admin:full';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof FieldGroup) {
            throw new MethodNotAllowedHttpException(['FieldGroup']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(FieldGroupType::class, $entity, $options);
    }

    public function getEntity($id = null): ?FieldGroup
    {
        if (null === $id) {
            return new FieldGroup();
        }

        $entity = parent::getEntity($id);

        // Return null (not an empty entity) when the id is unknown, so callers
        // and the API can produce a proper 404.
        return $entity instanceof FieldGroup ? $entity : null;
    }

    /**
     * Returns hardcoded default group aliases for a given object type.
     *
     * @return array<string>
     */
    public function getDefaultGroups(string $object = 'lead'): array
    {
        return 'company' === $object
            ? FieldGroup::DEFAULT_COMPANY_GROUPS
            : FieldGroup::DEFAULT_LEAD_GROUPS;
    }

    /**
     * Returns all groups (defaults + custom DB records) keyed by alias.
     *
     * @return array<string, string> [alias => displayName]
     */
    public function getGroups(string $object = 'lead'): array
    {
        $groups = [];

        foreach ($this->getDefaultGroups($object) as $alias) {
            $groups[$alias] = $this->translator->trans('mautic.lead.field.group.'.$alias);
        }

        $customGroups = $this->fieldGroupRepository->getEntities([
            'ignore_paginator' => true,
            'orderBy'          => 'fg.order',
            'orderByDir'       => 'ASC',
        ]);

        foreach ($customGroups as $entity) {
            $alias = $entity->getAlias();
            if ($alias && !isset($groups[$alias])) {
                $groups[$alias] = $entity->getName() ?? $alias;
            }
        }

        return $groups;
    }

    /**
     * Assigns an order to brand-new groups, then renumbers all groups so the
     * position chosen in the "Group order" dropdown is respected.
     */
    public function saveEntity($entity, $unlock = true): void
    {
        if ($entity instanceof FieldGroup) {
            // Generate a unique ASCII alias from the (possibly Unicode) name on create.
            $this->aliasHelper->makeAliasUnique($entity);

            if (!$entity->getId() && !$entity->getOrder()) {
                $entity->setOrder($this->fieldGroupRepository->getMaxOrder() + 1);
            }
        }

        parent::saveEntity($entity, $unlock);

        $this->reorderGroupsByEntity($entity);
    }

    /**
     * Inserts $entity into the slot of the group picked in the "Group order"
     * dropdown (i.e. directly before it), then renumbers every group
     * sequentially 1..n. Renumbering on each save guarantees distinct, gap-free
     * orders and avoids ties. When no target matches (new group, or the
     * dropdown was left empty) the group is appended to the end.
     */
    public function reorderGroupsByEntity(FieldGroup $entity): void
    {
        $target = $entity->getOrder();

        // All groups except the one being moved, in their current order.
        $others = array_values(array_filter(
            $this->fieldGroupRepository->findBy([], ['order' => 'ASC']),
            fn (FieldGroup $group): bool => $group->getId() !== $entity->getId()
        ));

        $ordered  = [];
        $inserted = false;
        foreach ($others as $group) {
            if (!$inserted && $group->getOrder() === $target) {
                $ordered[] = $entity;
                $inserted  = true;
            }
            $ordered[] = $group;
        }

        if (!$inserted) {
            $ordered[] = $entity;
        }

        $position = 1;
        foreach ($ordered as $group) {
            $group->setOrder($position++);
            $this->em->persist($group);
        }

        $this->em->flush();
    }

    /**
     * Reorders a grouped fields array (keyed by group alias) to match the
     * configured group order, so contact view/edit tabs follow it.
     *
     * @param array<string, mixed> $groupedFields [alias => fields]
     *
     * @return array<string, mixed>
     */
    public function sortGroupedFields(array $groupedFields, string $object = 'lead'): array
    {
        $ordered = [];

        foreach (array_keys($this->getGroups($object)) as $alias) {
            if (array_key_exists($alias, $groupedFields)) {
                $ordered[$alias] = $groupedFields[$alias];
            }
        }

        // Append any group present on the entity but unknown to the config.
        foreach ($groupedFields as $alias => $fields) {
            if (!array_key_exists($alias, $ordered)) {
                $ordered[$alias] = $fields;
            }
        }

        return $ordered;
    }

    /**
     * Dispatches FIELD_GROUP_LIST_ON_GENERATE and returns the resulting translated group map.
     *
     * @return array<string, string> [alias => displayName]
     */
    public function getTranslatedGroups(string $object = 'lead'): array
    {
        $groups = $this->getGroups($object);

        $event = new FieldGroupListEvent($groups, $object);
        $this->dispatcher->dispatch($event, LeadEvents::FIELD_GROUP_LIST_ON_GENERATE);

        return $event->getGroups();
    }

    /**
     * Returns field count per custom group alias.
     *
     * @return array<string, int>
     */
    public function getFieldCountPerGroup(): array
    {
        return $this->fieldGroupRepository->getFieldCountPerGroup();
    }

    /**
     * Backstop guard for the single-entity and API delete paths: a group that
     * still has fields assigned cannot be deleted, otherwise those fields would
     * be orphaned (lead_fields.field_group references the alias with no FK).
     * The GUI single delete also pre-checks via canDelete(); the API catches
     * DeleteEntityDependencyException and returns a clean error. Batch delete is
     * guarded in FieldGroupController::batchDeleteAction().
     *
     * @param FieldGroup $entity
     */
    public function deleteEntity($entity): void
    {
        if ($entity instanceof FieldGroup && !$this->canDelete($entity)) {
            throw new DeleteEntityDependencyException([$this->translator->trans('mautic.lead.field_group.error.has_fields', [], 'flashes')]);
        }

        parent::deleteEntity($entity);
    }

    public function canDelete(FieldGroup $entity): bool
    {
        $id = $entity->getId();

        return null === $id || !$this->fieldGroupRepository->hasFields($id);
    }
}
