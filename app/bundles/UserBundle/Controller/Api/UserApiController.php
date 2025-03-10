<?php

namespace Mautic\UserBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @extends CommonApiController<User>
 */
class UserApiController extends CommonApiController
{
    /**
     * @var UserModel|null
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        private UserPasswordHasherInterface $hasher,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $userModel     = $modelFactory->getModel('user.user');
        \assert($userModel instanceof UserModel);

        $this->model            = $userModel;
        $this->entityClass      = User::class;
        $this->entityNameOne    = 'user';
        $this->entityNameMulti  = 'users';
        $this->serializerGroups = ['userDetails', 'roleList', 'publishDetails'];
        $this->dataInputMasks   = ['signature' => 'html'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Obtains the logged in user's data.
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function getSelfAction(TokenStorageInterface $tokenStorage)
    {
        $currentUser = $tokenStorage->getToken()->getUser();
        $view        = $this->view($currentUser, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Creates a new user.
     */
    public function newEntityAction(Request $request)
    {
        $entity = $this->model->getEntity();

        if (!$this->security->isGranted('user:users:create')) {
            return $this->accessDenied();
        }

        $parameters = $request->request->all();

        if (isset($parameters['plainPassword']['password'])) {
            $submittedPassword = $parameters['plainPassword']['password'];
            $entity->setPassword($this->model->checkNewPassword($entity, $this->hasher, $submittedPassword));
        }

        return $this->processForm($request, $entity, $parameters, 'POST');
    }

    /**
     * Edits an existing user or creates a new one on PUT if not found.
     *
     * @param int $id User ID
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function editEntityAction(Request $request, $id)
    {
        $entity     = $this->model->getEntity($id);
        $parameters = $request->request->all();
        $method     = $request->getMethod();

        if (!$this->security->isGranted('user:users:edit')) {
            return $this->accessDenied();
        }

        if (null === $entity) {
            if ('PATCH' === $method
                || ('PUT' === $method && !$this->security->isGranted('user:users:create'))
            ) {
                // PATCH requires that an entity exists or must have create access for PUT
                return $this->notFound();
            } else {
                $entity = $this->model->getEntity();
                if (isset($parameters['plainPassword']['password'])) {
                    $submittedPassword = $parameters['plainPassword']['password'];
                    $entity->setPassword($this->model->checkNewPassword($entity, $this->hasher, $submittedPassword));
                }
            }
        } else {
            // Changing passwords via API is forbidden
            if (!empty($parameters['plainPassword'])) {
                unset($parameters['plainPassword']);
            }
            if ('PATCH' == $method) {
                // PATCH will accept a diff so just remove the entities

                // Changing username via API is forbidden
                if (!empty($parameters['username'])) {
                    unset($parameters['username']);
                }
            } else {
                // PUT requires the entire entity so overwrite the username with the original
                $parameters['username'] = $entity->getUsername();
                $parameters['role']     = $entity->getRole()->getId();
            }
        }

        return $this->processForm($request, $entity, $parameters, $method);
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

                $entity->setPassword($this->model->checkNewPassword($entity, $this->hasher, $submittedPassword, true));
                break;
        }
    }

    /**
     * Verifies if a user has permission(s) to a action.
     *
     * @param int $id User ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function isGrantedAction(Request $request, $id)
    {
        $entity = $this->model->getEntity($id);
        if (!$entity instanceof $this->entityClass) {
            return $this->notFound();
        }

        $permissions = $request->request->all()['permissions'] ?? [];

        if (empty($permissions)) {
            return $this->badRequest('mautic.api.call.permissionempty');
        } elseif (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        $return = $this->security->isGranted($permissions, 'RETURN_ARRAY', $entity);
        $view   = $this->view($return, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of roles for user edits.
     *
     * @return Response
     */
    public function getRolesAction(Request $request)
    {
        if (!$this->security->isGranted(
            ['user:users:create', 'user:users:edit'],
            'MATCH_ONE'
        )
        ) {
            return $this->accessDenied();
        }

        $filter = $request->query->get('filter', null);
        $limit  = (int) $request->query->get('limit', null);
        $roles  = $this->model->getLookupResults('role', $filter, $limit);

        $view    = $this->view($roles, Response::HTTP_OK);
        $context = $view->getContext()->setGroups(['roleList']);
        $view->setContext($context);

        return $this->handleView($view);
    }
}
