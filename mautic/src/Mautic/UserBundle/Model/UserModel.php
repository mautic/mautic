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
use Symfony\Component\HttpFoundation\Request;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class UserModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model
 */
class UserModel extends FormModel
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository     = 'MauticUserBundle:User';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @return int
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function saveEntity($entity)
    {
        if (!$entity instanceof User) {
            throw new NotFoundHttpException('Entity must be of class User()');
        }

        return parent::saveEntity($entity);
    }

    /**
     * Checks for a new password and rehashes if necessary
     *
     * @param User $entity
     * @param      $submittedPassword
     * @return int|string
     */
    public function checkNewPassword(User $entity, $submittedPassword) {
        if (!empty($submittedPassword)) {
            //hash the clear password submitted via the form
            $security = $this->container->get('security.encoder_factory');
            $encoder  = $security->getEncoder($entity);
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
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     */
    public function createForm($entity, $action = null)
    {
        if (!$entity instanceof User) {
            throw new NotFoundHttpException('Entity must be of class User()');
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $this->container->get('form.factory')->create('user', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = '')
    {
        if (empty($id)) {
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
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof User) {
            throw new NotFoundHttpException('Entity must be of class User()');
        }

        if (empty($event)) {
            $event = new UserEvent($entity, $isNew);
            $event->setEntityManager($this->em);
        }
        $dispatcher = $this->container->get('event_dispatcher');
        switch ($action) {
            case "pre_save":
                $dispatcher->dispatch(UserEvents::USER_PRE_SAVE, $event);
                break;
            case "post_save":
                $dispatcher->dispatch(UserEvents::USER_POST_SAVE, $event);
                break;
            case "pre_delete":
                $dispatcher->dispatch(UserEvents::USER_PRE_DELETE, $event);
                break;
            case "post_delete":
                $dispatcher->dispatch(UserEvents::USER_POST_DELETE, $event);
                break;
        }
        return $event;
    }
}