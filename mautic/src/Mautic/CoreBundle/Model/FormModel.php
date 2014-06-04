<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class FormModel
 *
 * @package Mautic\CoreBundle\Model
 */
class FormModel extends CommonModel
{

    /**
     * Get a specific entity
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if (null !== $id) {
            $repo = $this->em->getRepository($this->repository);
            if (method_exists($repo, 'getEntity')) {
                return $repo->getEntity($id);
            } else {
                return $repo->find($id);
            }
        } else {
            return null;
        }
    }

    /**
     * Return list of entities
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     * @return mixed
     */
    public function getEntities(array $args = array())
    {
        //set the translator
        $this->em->getRepository($this->repository)->setTranslator($this->translator);
        $this->em->getRepository($this->repository)->setCurrentUser(
            $this->security->getCurrentUser()
        );

        return $this->em
            ->getRepository($this->repository)
            ->getEntities($args);
    }

    /**
     * Lock an entity to prevent multiple people from editing
     *
     * @param $entity
     */
    public function lockEntity($entity)
    {
        //unlock the row if applicable
        if (method_exists($entity, 'setCheckedOut')) {
            $entity->setCheckedOut(new \DateTime());
            $entity->setCheckedOutBy($this->security->getCurrentUser());
        }

        $this->em
            ->getRepository($this->repository)
            ->saveEntity($entity);
    }

    /**
     * Check to see if the entity is locked
     *
     * @param $entity
     * @return bool
     */
    public function isLocked($entity)
    {
        if (method_exists($entity, 'getCheckedOut')) {
            $checkedOut = $entity->getCheckedOut();
            if (!empty($checkedOut)) {
                //is it checked out by the current user?
                $checkedOutBy = $entity->getCheckedOutBy();
                if (!empty($checkedOutBy) && $checkedOutBy->getId() !==
                    $this->security->getCurrentUser()->getId()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Unlock an entity to prevent multiple people from editing
     *
     * @param $entity
     */
    public function unlockEntity($entity)
    {
        //flush any changes first
        $this->em->refresh($entity);

        //unlock the row if applicable
        if (method_exists($entity, 'setCheckedOut')) {
            $entity->setCheckedOut(null);
            $entity->setCheckedOutBy(null);

            if (method_exists($entity, 'setModifiedBy')) {
                $entity->setModifiedBy($this->security->getCurrentUser());
            }
        }

        $this->em
            ->getRepository($this->repository)
            ->saveEntity($entity);
    }

    /**
     * Create/edit entity
     *
     * @param       $entity
     * @return mixed
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function saveEntity($entity)
    {
        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew);

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->em->getRepository($this->repository)->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);

        return $entity;
    }

    /**
     * Set timestamps and user ids
     *
     * @param $entity
     * @param $isNew
     */
    public function setTimestamps(&$entity, $isNew)
    {
        if ($isNew) {
            if (method_exists($entity, 'setDateAdded') && !$entity->getDateAdded()) {
                $entity->setDateAdded(new \DateTime());
            }

            if (method_exists($entity, 'setCreatedBy') && !$entity->getCreatedBy()) {
                $entity->setCreatedBy($this->security->getCurrentUser());
            }
        } else {
            if (method_exists($entity, 'setDateModified') && !$entity->getDateModified()) {
                $entity->setDateModified(new \DateTime());
            }

            if (method_exists($entity, 'setModifiedBy') && !$entity->getModifiedBy()) {
                $entity->setModifiedBy($this->security->getCurrentUser());
            }
        }

        //unlock the row if applicable
        if (method_exists($entity, 'setCheckedOut')) {
            $entity->setCheckedOut(null);
            $entity->setCheckedOutBy(null);
        }
    }

    /**
     * Delete an entity
     *
     * @param  $entity
     * @return null|object
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteEntity($entity)
    {
        $event = $this->dispatchEvent("pre_delete", $entity);
        $this->em->getRepository($this->repository)->deleteEntity($entity);
        $this->dispatchEvent("post_delete", $entity, $event);

        return $entity;
    }

    /**
     * Creates the appropriate form per the model
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null)
    {
        throw new NotFoundHttpException('Form object not found.');
    }

    /**
     * Dispatches events for child classes
     *
     * @param $action
     * @param $entity
     * @param $isNew
     * @param $event
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        //...
    }

    /**
     * Set default subject for user contact form
     *
     * @param $subject
     * @param $entity
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

        $subject = $this->translator->trans($msg, array(
            '%entityName%' => $entity->getName(),
            '%entityId%'   => $entity->getId()
        ));

        return $subject;
    }
}