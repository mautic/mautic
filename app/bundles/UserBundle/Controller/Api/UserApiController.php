<?php

namespace Mautic\UserBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @extends CommonApiController<User>
 */
class UserApiController extends CommonApiController
{
    /**
     * @var UserModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $userModel = $this->getModel('user.user');
        \assert($userModel instanceof UserModel);

        $this->model            = $userModel;
        $this->entityClass      = User::class;
        $this->entityNameOne    = 'user';
        $this->entityNameMulti  = 'users';
        $this->serializerGroups = ['userDetails', 'roleList', 'publishDetails'];
        $this->dataInputMasks   = ['signature' => 'html'];
        parent::initialize($event);
    }

    /**
     * Obtains the logged in user's data.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getSelfAction()
    {
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        $view        = $this->view($currentUser, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Creates a new user.
     */
    public function newEntityAction()
    {
        $entity = $this->model->getEntity();

        if (!$this->get('mautic.security')->isGranted('user:users:create')) {
            return $this->accessDenied();
        }

        $parameters = $this->request->request->all();

        if (isset($parameters['plainPassword']['password'])) {
            $submittedPassword = $parameters['plainPassword']['password'];
            $encoder           = $this->get('security.password_encoder');
            $entity->setPassword($this->model->checkNewPassword($entity, $encoder, $submittedPassword));
        }

        return $this->processForm($entity, $parameters, 'POST');
    }

    /**
     * Edits an existing user or creates a new one on PUT if not found.
     *
     * @param int $id User ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws NotFoundHttpException
     */
    public function editEntityAction($id)
    {
        $entity     = $this->model->getEntity($id);
        $parameters = $this->request->request->all();
        $method     = $this->request->getMethod();

        if (!$this->get('mautic.security')->isGranted('user:users:edit')) {
            return $this->accessDenied();
        }

        if (null === $entity) {
            if ('PATCH' === $method ||
                ('PUT' === $method && !$this->get('mautic.security')->isGranted('user:users:create'))
            ) {
                //PATCH requires that an entity exists or must have create access for PUT
                return $this->notFound();
            } else {
                $entity = $this->model->getEntity();
                if (isset($parameters['plainPassword']['password'])) {
                    $submittedPassword = $parameters['plainPassword']['password'];
                    $encoder           = $this->get('security.password_encoder');
                    $entity->setPassword($this->model->checkNewPassword($entity, $encoder, $submittedPassword));
                }
            }
        } else {
            //Changing passwords via API is forbidden
            if (!empty($parameters['plainPassword'])) {
                unset($parameters['plainPassword']);
            }
            if ('PATCH' == $method) {
                //PATCH will accept a diff so just remove the entities

                //Changing username via API is forbidden
                if (!empty($parameters['username'])) {
                    unset($parameters['username']);
                }
            } else {
                //PUT requires the entire entity so overwrite the username with the original
                $parameters['username'] = $entity->getUsername();
                $parameters['role']     = $entity->getRole()->getId();
            }
        }

        return $this->processForm($entity, $parameters, $method);
    }

    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        switch ($action) {
            case 'new':
                $submittedPassword = null;
                if (isset($parameters['plainPassword'])) {
                    if (is_array($parameters['plainPassword']) && isset($parameters['plainPassword']['password'])) {
                        $submittedPassword = $parameters['plainPassword']['password'];
                    } else {
                        $submittedPassword = $parameters['plainPassword'];
                    }
                }

                $encoder = $this->get('security.password_encoder');
                $entity->setPassword($this->model->checkNewPassword($entity, $encoder, $submittedPassword, true));
                break;
        }
    }

    /**
     * Verifies if a user has permission(s) to a action.
     *
     * @param int $id User ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
     */
    public function isGrantedAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (!$entity instanceof $this->entityClass) {
            return $this->notFound();
        }

        $permissions = $this->request->request->get('permissions');

        if (empty($permissions)) {
            return $this->badRequest('mautic.api.call.permissionempty');
        } elseif (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        $return = $this->get('mautic.security')->isGranted($permissions, 'RETURN_ARRAY', $entity);
        $view   = $this->view($return, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of roles for user edits.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRolesAction()
    {
        if (!$this->get('mautic.security')->isGranted(
            ['user:users:create', 'user:users:edit'],
            'MATCH_ONE'
        )
        ) {
            return $this->accessDenied();
        }

        $filter = $this->request->query->get('filter', null);
        $limit  = $this->request->query->get('limit', null);
        $roles  = $this->model->getLookupResults('role', $filter, $limit);

        $view    = $this->view($roles, Response::HTTP_OK);
        $context = $view->getContext()->setGroups(['roleList']);
        $view->setContext($context);

        return $this->handleView($view);
    }
}
