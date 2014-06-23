<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class UserModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\FormModel
 */
class UserModel extends FormModel
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository = 'MauticUserBundle:User';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @return int
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function saveEntity($entity)
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(array('User'), 'Entity must be of class User()');
        }

        parent::saveEntity($entity);
    }

    /**
     * Checks for a new password and rehashes if necessary
     *
     * @param User $entity
     * @param      $encoder
     * @param      $submittedPassword
     * @return int|string
     */
    public function checkNewPassword(User $entity, $encoder, $submittedPassword) {
        if (!empty($submittedPassword)) {
            //hash the clear password submitted via the form
            $password = $encoder->encodePassword($submittedPassword, $entity->getSalt());
        } else {
            //get the original password to save if password is empty from the form
            $originalPassword = $entity->getPassword();
            //This is an existing user with a blank password so set the original password
            $password = $originalPassword;
        }

        return $password;
    }


    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(array('User'), 'Entity must be of class User()');
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create('user', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new User();
        }

        $entity = parent::getEntity($id);

        if ($entity) {
            //add user's permissions
            $entity->setActivePermissions(
                $this->em->getRepository('MauticUserBundle:Permission')->getPermissionsByRole($entity->getRole())
            );
        }

        return $entity;
    }

    /**
     *  {@inheritdoc}
     *
     * @param      $action
     * @param      $entity
     * @param bool $isNew
     * @param      $event
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(array('User'), 'Entity must be of class User()');
        }

        switch ($action) {
            case "pre_save":
                $name = UserEvents::USER_PRE_SAVE;
                break;
            case "post_save":
                $name = UserEvents::USER_POST_SAVE;
                break;
            case "pre_delete":
                $name = UserEvents::USER_PRE_DELETE;
                break;
            case "post_delete":
                $name = UserEvents::USER_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {

            if (empty($event)) {
                $event = new UserEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'role':
                $results = $this->em->getRepository('MauticUserBundle:Role')->getRoleList($filter, $limit);
                break;
            case 'position':
                $results = $this->em->getRepository('MauticUserBundle:User')->getPositionList($filter, $limit);
                break;
        }

        return $results;
    }
}