<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Model;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Entity\oAuth1\Consumer;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\ApiBundle\Event\ClientEvent;
use Mautic\ApiBundle\Form\Type\ClientType;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ClientModel extends FormModel
{
    /**
     * @var string
     */
    const API_MODE_OAUTH2 = 'oauth2';

    /**
     * @var string
     */
    private $apiMode = 'oauth1a';

    /**
     * @var Session
     */
    protected $session;

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();

        if ($request) {
            $this->apiMode = $request->get('api_mode', $request->getSession()->get('mautic.client.filter.api_mode', 'oauth1a'));
        }
    }

    /**
     * @param $apiMode
     */
    public function setApiMode($apiMode)
    {
        $this->apiMode = $apiMode;
    }

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\ApiBundle\Entity\oAuth1\ConsumerRepository|\Mautic\ApiBundle\Entity\oAuth2\ClientRepository
     */
    public function getRepository()
    {
        if ('oauth2' == $this->apiMode) {
            return $this->em->getRepository(Client::class);
        } else {
            return $this->em->getRepository(Consumer::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'api:clients';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Client && !$entity instanceof Consumer) {
            throw new MethodNotAllowedHttpException(['Client', 'Consumer']);
        }

        $params = (!empty($action)) ? ['action' => $action] : [];

        return $formFactory->create(ClientType::class, $entity, $params);
    }

    /**
     * {@inheritdoc}
     *
     * @return Client|Consumer|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return 'oauth2' == $this->apiMode ? new Client() : new Consumer();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Client && !$entity instanceof Consumer) {
            throw new MethodNotAllowedHttpException(['Client', 'Consumer']);
        }

        switch ($action) {
            case 'post_save':
                $name = ApiEvents::CLIENT_POST_SAVE;
                break;
            case 'post_delete':
                $name = ApiEvents::CLIENT_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ClientEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getUserClients(User $user)
    {
        return $this->getRepository()->getUserClients($user);
    }

    /**
     * @param $entity
     *
     * @throws MethodNotAllowedHttpException
     */
    public function revokeAccess($entity)
    {
        if (!$entity instanceof Client && !$entity instanceof Consumer) {
            throw new MethodNotAllowedHttpException(['Client', 'Consumer']);
        }

        //remove the user from the client
        if ('oauth2' == $this->apiMode) {
            $entity->removeUser($this->userHelper->getUser());
            $this->saveEntity($entity);
        } else {
            $this->getRepository()->deleteAccessTokens($entity, $this->userHelper->getUser());
        }
    }
}
