<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Model;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ClientEvent;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ApiBundle\Entity\Client;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ClientModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model
 */
class ClientModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticApiBundle:Client');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'api:clients';
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Client) {
            throw new MethodNotAllowedHttpException(array('Client'), 'Entity must be of class Client()');
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('client', $entity, $params);
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
            return new Client();
        }

        return parent::getEntity($id);
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
        if (!$entity instanceof Client) {
            throw new MethodNotAllowedHttpException(array('Client'), 'Entity must be of class Client()');
        }

        switch ($action) {
            case "post_save":
                $name = ApiEvents::CLIENT_POST_SAVE;
                break;
            case "post_delete":
                $name = ApiEvents::CLIENT_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ClientEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch(ApiEvents::CLIENT_POST_SAVE, $event);
            return $event;
        } else {
            return false;
        }
    }

    public function getUserClients(User $user)
    {
        return $this->getRepository()->getUserClients($user);
    }
}