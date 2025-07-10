<?php

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\UnitOfWork;
use Mautic\CoreBundle\Entity\SkipModifiedInterface;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @template T of object
 *
 * @extends AbstractCommonModel<T>
 */
class FormModel extends AbstractCommonModel
{
    /**
     * Lock an entity to prevent multiple people from editing.
     *
     * @param object $entity
     */
    public function lockEntity($entity): void
    {
        // lock the row if applicable
        if (method_exists($entity, 'setCheckedOut') && method_exists($entity, 'getId') && $entity->getId()) {
            if ($this->userHelper->getUser()->getId()) {
                $entity->setCheckedOut(new \DateTime());
                $entity->setCheckedOutBy($this->userHelper->getUser());
                $this->em->persist($entity);
                $this->em->flush();
            }
        }
    }

    /**
     * Check to see if the entity is locked.
     *
     * @param object $entity
     */
    public function isLocked($entity): bool
    {
        if (method_exists($entity, 'getCheckedOut')) {
            $checkedOut = $entity->getCheckedOut();
            if (!empty($checkedOut) && $checkedOut instanceof \DateTime) {
                $checkedOutBy     = $entity->getCheckedOutBy();
                $maxLockTime      = $this->coreParametersHelper->get('max_entity_lock_time', 0);
                $lockValidityDate = false;

                if (0 != $maxLockTime && is_numeric($maxLockTime)) {
                    $lockValidityDate = clone $checkedOut;
                    $lockValidityDate->add(new \DateInterval('PT'.$maxLockTime.'S'));
                }

                // is lock expired ?
                if (false !== $lockValidityDate && (new \DateTime()) > $lockValidityDate) {
                    return false;
                }

                // is it checked out by the current user?
                if (!empty($checkedOutBy) && ($checkedOutBy !== $this->userHelper->getUser()->getId())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Unlock an entity that prevents multiple people from editing.
     *
     * @param object $entity
     * @param        $extra  Can be used by model to determine what to unlock
     */
    public function unlockEntity($entity, $extra = null): void
    {
        // unlock the row if applicable
        if (method_exists($entity, 'setCheckedOut') && method_exists($entity, 'getId') && $entity->getId()) {
            // flush any potential changes
            $this->em->refresh($entity);

            $entity->setCheckedOut(null);
            $entity->setCheckedOutBy(null);

            $this->em->persist($entity);
            $this->em->flush();
        }
    }

    /**
     * Create/edit entity.
     *
     * @param object $entity
     * @param bool   $unlock
     *
     * @phpstan-param T $entity
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $isNew = $this->isNewEntity($entity);

        // set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $event = $this->dispatchEvent('pre_save', $entity, $isNew);
        $this->getRepository()->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, $isNew, $event);
    }

    /**
     * Create/edit entity then detach to preserve RAM.
     *
     * @param bool $unlock
     */
    public function saveAndDetachEntity($entity, $unlock = true): void
    {
        $this->saveEntity($entity, $unlock);

        $this->em->detach($entity);
    }

    /**
     * Save an array of entities.
     *
     * @param iterable<T> $entities
     * @param bool        $unlock
     */
    public function saveEntities($entities, $unlock = true): void
    {
        // iterate over the results so the events are dispatched on each delete
        $batchSize             = 20;
        $entitiesPreSaveParams = [];
        foreach ($entities as &$entity) {
            $isNew = $this->isNewEntity($entity);

            // set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            // Pre save single dispatcher
            $preEvent                = $this->dispatchEventFromBatch('pre_save', $entity, $isNew);
            $entitiesPreSaveParams[] = ['entity' => $entity, 'isNew' => $isNew, 'event' => $preEvent];
        }

        // Pre save batch dispatcher
        $preBatchEvent = $this->dispatchBatchEvent('pre_batch_save', $entitiesPreSaveParams);

        // Saving in batches
        $loops = 0;
        foreach ($entitiesPreSaveParams as $entityPreSaveParams) {
            $this->getRepository()->saveEntity($entityPreSaveParams['entity'], false);
            if (0 === ++$loops % $batchSize) {
                $this->em->flush();
            }
        }
        if (0 !== $loops % $batchSize) {
            $this->em->flush();
        }

        // Dispatch after flush
        $entitiesPostSaveParams = [];
        foreach ($entitiesPreSaveParams as &$entityParams) {
            // Post save single dispatcher after flush
            $postEvent                = $this->dispatchEventFromBatch('post_save', $entityParams['entity'], $entityParams['isNew'], $entityParams['event']);
            $entitiesPostSaveParams[] = ['entity' => $entityParams['entity'], 'isNew' => $entityParams['isNew'], 'event' => $postEvent];
        }

        // Post save batch dispatcher
        $this->dispatchBatchEvent('post_batch_save', $entitiesPostSaveParams, $preBatchEvent);
    }

    /**
     * Determines if an entity is new or not.
     *
     * @param mixed $entity
     *
     * @return bool
     */
    public function isNewEntity($entity)
    {
        if (method_exists($entity, 'isNew')) {
            return $entity->isNew();
        }

        if (method_exists($entity, 'getId')) {
            return !$entity->getId();
        }

        return UnitOfWork::STATE_NEW === $this->em->getUnitOfWork()->getEntityState($entity);
    }

    /**
     * Toggles entity publish status.
     *
     * @param object $entity
     *
     * @return bool Force browser refresh
     */
    public function togglePublishStatus($entity): bool
    {
        if (method_exists($entity, 'setIsPublished')) {
            $status = $entity->getPublishStatus();

            switch ($status) {
                case 'unpublished':
                    $entity->setIsPublished(true);
                    break;
                case 'expired':
                case 'pending':
                case 'published':
                    $this->dispatchEvent('pre_unpublish', $entity);
                    $entity->setIsPublished(false);
                    break;
            }

            // set timestamp changes
            $this->setTimestamps($entity, false, false);
        } elseif (method_exists($entity, 'setIsEnabled')) {
            $entity->setIsEnabled(!$entity->getIsEnabled());
        }

        // hit up event listeners
        $event = $this->dispatchEvent('pre_save', $entity);
        $this->getRepository()->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, false, $event);

        return false;
    }

    /**
     * Set timestamps and user ids.
     *
     * @param object $entity
     * @param bool   $isNew
     * @param bool   $unlock
     */
    public function setTimestamps(&$entity, $isNew, $unlock = true): void
    {
        // unlock the row if applicable
        if ($unlock && method_exists($entity, 'setCheckedOut')) {
            $entity->setCheckedOut(null);
            $entity->setCheckedOutBy(null);
        }

        if ($isNew) {
            if (method_exists($entity, 'setDateAdded') && !$entity->getDateAdded()) {
                $entity->setDateAdded(new \DateTime());
            }

            if (($user = $this->userHelper->getUser()) instanceof User) {
                if (method_exists($entity, 'setCreatedBy') && !$entity->getCreatedBy()) {
                    $entity->setCreatedBy($user);
                } elseif (method_exists($entity, 'setCreatedByUser') && !$entity->getCreatedByUser()) {
                    $entity->setCreatedByUser($user->getName());
                }
            }

            $this->setModifiedData($entity);

            return;
        }

        if ($entity instanceof SkipModifiedInterface && $entity->shouldSkipSettingModifiedProperties()) {
            return;
        }

        if (method_exists($entity, 'getChanges') ? !empty($entity->getChanges()) : true) {
            $this->setModifiedData($entity);
        }
    }

    private function setModifiedData(object $entity): void
    {
        if (method_exists($entity, 'setDateModified') && method_exists($entity, 'getDateModified') && !$entity->getDateModified()) {
            $entity->setDateModified(
                defined('MAUTIC_DATE_MODIFIED_OVERRIDE') ? \DateTime::createFromFormat('U', MAUTIC_DATE_MODIFIED_OVERRIDE) : new \DateTime()
            );
        }

        if (($user = $this->userHelper->getUser()) instanceof User) {
            if (method_exists($entity, 'setModifiedBy')) {
                $entity->setModifiedBy($user);
            } elseif (method_exists($entity, 'setModifiedByUser')) {
                $entity->setModifiedByUser($user->getName());
            }
        }
    }

    /**
     * Delete an entity.
     *
     * @param object $entity
     */
    public function deleteEntity($entity): void
    {
        // take note of ID before doctrine wipes it out
        $id    = $entity->getId();
        $event = $this->dispatchEvent('pre_delete', $entity);
        $this->getRepository()->deleteEntity($entity);

        // set the id for use in events
        $entity->deletedId = $id;
        $this->dispatchEvent('post_delete', $entity, false, $event);
    }

    /**
     * Delete an array of entities.
     *
     * @param mixed[] $ids
     *
     * @return mixed[]
     */
    public function deleteEntities($ids): array
    {
        $entities = [];
        // iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($ids as $k => $id) {
            $entity        = $this->getEntity($id);
            $entities[$id] = $entity;
            if (null !== $entity) {
                $event = $this->dispatchEvent('pre_delete', $entity);
                $this->getRepository()->deleteEntity($entity, false);
                // set the id for use in events
                $entity->deletedId = $id;
                $this->dispatchEvent('post_delete', $entity, false, $event);
            }
            if (0 === (($k + 1) % $batchSize)) {
                $this->em->flush();
            }
        }
        $this->em->flush();

        // retrieving the entities while here so may as well return them so they can be used if needed
        return $entities;
    }

    /**
     * Creates the appropriate form per the model.
     *
     * @param object      $entity
     * @param string|null $action
     * @param array       $options
     *
     * @return FormInterface<mixed>
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        throw new NotFoundHttpException('Object does not support edits.');
    }

    /**
     * Dispatches events for child classes.
     *
     * @param string $action
     * @param object $entity
     * @param bool   $isNew
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        // ...

        return $event;
    }

    /**
     * Dispatches events for child classes.
     */
    protected function dispatchEventFromBatch(string $action, object &$entity, bool $isNew = false, Event $event = null): ?Event
    {
        return $this->dispatchEvent($action, $entity, $isNew, $event);
    }

    /**
     * Dispatches batch events for child classes.
     *
     * @param mixed[] $entitiesBatchParams
     */
    protected function dispatchBatchEvent(string $action, array &$entitiesBatchParams, Event $event = null): ?Event
    {
        return $event;
    }

    /**
     * Set default subject for user contact form.
     *
     * @param string $subject
     * @param object $entity
     */
    public function getUserContactSubject($subject, $entity): string
    {
        $msg = match ($subject) {
            'locked' => 'mautic.user.user.contact.locked',
            default  => 'mautic.user.user.contact.regarding',
        };

        $nameGetter = $this->getNameGetter();

        return $this->translator->trans($msg, [
            '%entityName%' => $entity->$nameGetter(),
            '%entityId%'   => $entity->getId(),
        ]);
    }

    /**
     * Returns the function used to name the entity.
     */
    public function getNameGetter(): string
    {
        return 'getName';
    }

    /**
     * Cleans a string to be used as an alias. The returned string will be alphanumeric or underscore, less than 25 characters
     * and if it is a reserved SQL keyword, it will be prefixed with f_.
     *
     * @param string   $prefix            Used when the alias is a reserved keyword by the database platform
     * @param int      $maxLength         Maximum number of characters used; 0 to disable
     * @param string   $spaceCharacter    Character to replace spaces with
     * @param string[] $allowedCharacters Allowed characters in alias
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function cleanAlias(
        string $alias,
        string $prefix = '',
        int $maxLength = 0,
        string $spaceCharacter = '_',
        array $allowedCharacters = [],
    ): string {
        // Transliterate to latin characters
        $alias = InputHelper::transliterate(trim($alias));

        // Some labels are quite long if a question so cut this short
        $alias = strtolower(InputHelper::alphanum($alias, false, $spaceCharacter, $allowedCharacters));

        // Ensure we have something
        if (empty($alias)) {
            $alias = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);
        }

        // Trim if applicable
        if ($maxLength) {
            $alias = substr($alias, 0, $maxLength);
        }

        if (str_ends_with($alias, '_')) {
            $alias = substr($alias, 0, -1);
        }

        // Check that alias is SQL safe since it will be used for the column name
        $databasePlatform = $this->em->getConnection()->getDatabasePlatform();
        $reservedWords    = $databasePlatform->getReservedKeywordsList();

        if ($reservedWords->isKeyword($alias) || is_numeric($alias)) {
            $alias = $prefix.$alias;
        }

        return $alias;
    }

    /**
     * Catch the exception in production and log the error.
     * Throw the exception in the dev mode only.
     */
    protected function flushAndCatch()
    {
        try {
            $this->em->flush();
        } catch (\Exception $ex) {
            if (MAUTIC_ENV === 'dev') {
                throw $ex;
            }

            $this->logger->error(
                $ex->getMessage(),
                ['exception' => $ex]
            );
        }
    }
}
