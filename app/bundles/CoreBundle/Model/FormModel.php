<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class FormModel.
 */
class FormModel extends AbstractCommonModel
{
    /**
     * Lock an entity to prevent multiple people from editing.
     *
     * @param object $entity
     */
    public function lockEntity($entity)
    {
        //lock the row if applicable
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
     *
     * @return bool
     */
    public function isLocked($entity)
    {
        if (method_exists($entity, 'getCheckedOut')) {
            $checkedOut = $entity->getCheckedOut();
            if (!empty($checkedOut) && $checkedOut instanceof \DateTime) {
                $checkedOutBy = $entity->getCheckedOutBy();
                $maxLockTime  = $this->factory->getParameter('max_entity_lock_time', 0);

                if ($maxLockTime !== 0 && is_numeric($maxLockTime)) {
                    $lockValidityDate = clone $checkedOut;
                    $lockValidityDate->add(new \DateInterval('PT'.$maxLockTime.'S'));
                } else {
                    $lockValidityDate = false;
                }

                //is lock expired ?
                if ($lockValidityDate !== false && (new \DateTime()) > $lockValidityDate) {
                    return false;
                }

                //is it checked out by the current user?
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
    public function unlockEntity($entity, $extra = null)
    {
        //unlock the row if applicable
        if (method_exists($entity, 'setCheckedOut') && method_exists($entity, 'getId') && $entity->getId()) {
            //flush any potential changes
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
     */
    public function saveEntity($entity, $unlock = true)
    {
        $isNew = $this->isNewEntity($entity);

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $event = $this->dispatchEvent('pre_save', $entity, $isNew);
        $this->getRepository()->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, $isNew, $event);
    }

    /**
     * Save an array of entities.
     *
     * @param array $entities
     * @param bool  $unlock
     *
     * @return array
     */
    public function saveEntities($entities, $unlock = true)
    {
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($entities as $k => $entity) {
            $isNew = $this->isNewEntity($entity);

            //set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            $event = $this->dispatchEvent('pre_save', $entity, $isNew);
            $this->getRepository()->saveEntity($entity, false);

            if ((($k + 1) % $batchSize) === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();

        // Dispatch post events after everything has been flushed
        foreach ($entities as $k => $entity) {
            $this->dispatchEvent('post_save', $entity, $isNew, $event);
        }
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
            $isNew = ($entity->getId()) ? false : true;
        } else {
            $isNew = \Doctrine\ORM\UnitOfWork::STATE_NEW === $this->em->getUnitOfWork()->getEntityState($entity);
        }

        return $isNew;
    }

    /**
     * Toggles entity publish status.
     *
     * @param object $entity
     *
     * @return bool Force browser refresh
     */
    public function togglePublishStatus($entity)
    {
        if (method_exists($entity, 'setIsPublished')) {
            $status = $entity->getPublishStatus();

            switch ($status) {
                case 'unpublished':
                    $entity->setIsPublished(true);
                    break;
                case 'published':
                case 'expired':
                case 'pending':
                    $entity->setIsPublished(false);
                    break;
            }

            //set timestamp changes
            $this->setTimestamps($entity, false, false);
        } elseif (method_exists($entity, 'setIsEnabled')) {
            $enabled    = $entity->getIsEnabled();
            $newSetting = ($enabled) ? false : true;
            $entity->setIsEnabled($newSetting);
        }

        //hit up event listeners
        $event = $this->dispatchEvent('pre_save', $entity, false);
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
    public function setTimestamps(&$entity, $isNew, $unlock = true)
    {
        if ($isNew) {
            if (method_exists($entity, 'setDateAdded') && !$entity->getDateAdded()) {
                $entity->setDateAdded(new \DateTime());
            }

            if ($this->userHelper->getUser() instanceof User) {
                if (method_exists($entity, 'setCreatedBy') && !$entity->getCreatedBy()) {
                    $entity->setCreatedBy($this->userHelper->getUser());
                } elseif (method_exists($entity, 'setCreatedByUser') && !$entity->getCreatedByUser()) {
                    $entity->setCreatedByUser($this->userHelper->getUser()->getName());
                }
            }
        } else {
            if (method_exists($entity, 'setDateModified')) {
                $entity->setDateModified(new \DateTime());
            }

            if ($this->userHelper->getUser() instanceof User) {
                if (method_exists($entity, 'setModifiedBy')) {
                    $entity->setModifiedBy($this->userHelper->getUser());
                } elseif (method_exists($entity, 'setModifiedByUser')) {
                    $entity->setModifiedByUser($this->userHelper->getUser()->getName());
                }
            }
        }

        //unlock the row if applicable
        if ($unlock && method_exists($entity, 'setCheckedOut')) {
            $entity->setCheckedOut(null);
            $entity->setCheckedOutBy(null);
        }
    }

    /**
     * Delete an entity.
     *
     * @param object $entity
     */
    public function deleteEntity($entity)
    {
        //take note of ID before doctrine wipes it out
        $id    = $entity->getId();
        $event = $this->dispatchEvent('pre_delete', $entity);
        $this->getRepository()->deleteEntity($entity);
        //set the id for use in events
        $entity->deletedId = $id;
        $this->dispatchEvent('post_delete', $entity, false, $event);
    }

    /**
     * Delete an array of entities.
     *
     * @param array $ids
     *
     * @return array
     */
    public function deleteEntities($ids)
    {
        $entities = [];
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($ids as $k => $id) {
            $entity        = $this->getEntity($id);
            $entities[$id] = $entity;
            if ($entity !== null) {
                $event = $this->dispatchEvent('pre_delete', $entity);
                $this->getRepository()->deleteEntity($entity, false);
                //set the id for use in events
                $entity->deletedId = $id;
                $this->dispatchEvent('post_delete', $entity, false, $event);
            }
            if ((($k + 1) % $batchSize) === 0) {
                $this->em->flush();
            }
        }
        $this->em->flush();
        //retrieving the entities while here so may as well return them so they can be used if needed
        return $entities;
    }

    /**
     * Creates the appropriate form per the model.
     *
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\Form
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        throw new NotFoundHttpException('Object does not support edits.');
    }

    /**
     * Dispatches events for child classes.
     *
     * @param       $action
     * @param       $entity
     * @param bool  $isNew
     * @param Event $event
     *
     * @return Event|null
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        //...

        return $event;
    }

    /**
     * Set default subject for user contact form.
     *
     * @param string $subject
     * @param object $entity
     *
     * @return mixed
     */
    public function getUserContactSubject($subject, $entity)
    {
        switch ($subject) {
            case 'locked':
                $msg = 'mautic.user.user.contact.locked';
                break;
            default:
                $msg = 'mautic.user.user.contact.regarding';
                break;
        }

        $nameGetter = $this->getNameGetter();
        $subject    = $this->translator->trans($msg, [
            '%entityName%' => $entity->$nameGetter(),
            '%entityId%'   => $entity->getId(),
        ]);

        return $subject;
    }

    /**
     * Returns the function used to name the entity.
     *
     * @return string
     */
    public function getNameGetter()
    {
        return 'getName';
    }

    /**
     * Cleans a string to be used as an alias. The returned string will be alphanumeric or underscore, less than 25 characters
     * and if it is a reserved SQL keyword, it will be prefixed with f_.
     *
     * @param string   $alias
     * @param string   $prefix         Used when the alias is a reserved keyword by the database platform
     * @param int|bool $maxLength      Maximum number of characters used; 0 to disable
     * @param string   $spaceCharacter Character to replace spaces with
     *
     * @return string
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function cleanAlias($alias, $prefix = '', $maxLength = false, $spaceCharacter = '_')
    {
        // Transliterate to latin characters
        $alias = InputHelper::transliterate(trim($alias));

        // Some labels are quite long if a question so cut this short
        $alias = strtolower(InputHelper::alphanum($alias, false, $spaceCharacter));

        // Ensure we have something
        if (empty($alias)) {
            $alias = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);
        }

        // Trim if applicable
        if ($maxLength) {
            $alias = substr($alias, 0, $maxLength);
        }

        if (substr($alias, -1) == '_') {
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
}
