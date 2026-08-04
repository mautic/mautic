<?php

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\UserBundle\Entity;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Contracts\Service\Attribute\Required;

final class RoleController extends FormController
{
    private const PERMISSION_VIEW = 'user:roles:view';

    private const PERMISSION_CREATE = 'user:roles:create';

    private const PERMISSION_EDIT   = 'user:roles:edit';

    private const PERMISSION_DELETE = 'user:roles:delete';

    private const FLASH_MENU_LINK = '%menu_link%';

    private const FLASH_URL       = '%url%';

    private const TEMPLATE_FORM = '@MauticUser/Role/form.html.twig';

    private RoleModel $roleModel;
    private PageHelperFactoryInterface $pageHelperFactory;

    #[Required]
    public function autowireRoleController(
        RoleModel $roleModel,
        PageHelperFactoryInterface $pageHelperFactory,
    ): void {
        $this->roleModel = $roleModel;
        $this->pageHelperFactory = $pageHelperFactory;
    }

    /**
     * @param int|string|null $objectId
     */
    protected function getSessionBase($objectId = null): string
    {
        $base = 'role';

        if (null !== $objectId) {
            $base .= '.'.$objectId;
        }

        return $base;
    }

    /**
     * Generate's default role list view.
     */
    public function indexAction(Request $request, int $page = 1): Response
    {
        if (!$this->security->isGranted(self::PERMISSION_VIEW)) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $pageHelper = $this->pageHelperFactory->make('mautic.role', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $orderBy    = $request->getSession()->get('mautic.role.orderby', 'r.name');
        $orderByDir = $request->getSession()->get('mautic.role.orderbydir', 'ASC');
        $filter     = $request->get('search', $request->getSession()->get('mautic.role.filter', ''));
        $tmpl       = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';
        $items = $this->roleModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $request->getSession()->set('mautic.role.filter', $filter);

        $count = count($items);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_role_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => 'Mautic\UserBundle\Controller\RoleController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_role_index',
                    'mauticContent' => 'role',
                ],
            ]);
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView([
            'viewParameters'  => [
                'items'       => $items,
                'searchValue' => $filter,
                'page'        => $page,
                'limit'       => $limit,
                'tmpl'        => $tmpl,
                'permissions' => [
                    'create' => $this->security->isGranted(self::PERMISSION_CREATE),
                    'edit'   => $this->security->isGranted(self::PERMISSION_EDIT),
                    'delete' => $this->security->isGranted(self::PERMISSION_DELETE),
                ],
            ],
            'contentTemplate' => '@MauticUser/Role/list.html.twig',
            'passthroughVars' => [
                'route'         => $this->generateUrl('mautic_role_index', ['page' => $page]),
                'mauticContent' => 'role',
            ],
        ]);
    }

    /**
     * Generate's new role form and processes post data.
     */
    public function newAction(Request $request): Response
    {
        if (!$this->security->isGranted(self::PERMISSION_CREATE)) {
            $this->throwAccessDenied();
        }

        // retrieve the entity
        $entity = new Entity\Role();

        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_role_index');

        // set the page we came from
        $page   = $request->getSession()->get('mautic.role.page', 1);
        $action = $this->generateUrl('mautic_role_action', ['objectAction' => 'new']);

        // get the user form factory
        $permissionsConfig = $this->getPermissionsConfig($entity);
        $form              = $this->roleModel->createForm($entity, $this->formFactory, $action, ['permissionsConfig' => $permissionsConfig['config']]);

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // set the permissions
                    $role        = $request->request->all()['role'] ?? [];
                    $permissions = $role['permissions'] ?? null;
                    $this->roleModel->setRolePermissions($entity, $permissions);

                    // form is valid so process the data
                    $this->roleModel->saveEntity($entity);

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'              => $entity->getName(),
                        self::FLASH_MENU_LINK => 'mautic_role_index',
                        self::FLASH_URL       => $this->generateUrl('mautic_role_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\UserBundle\Controller\RoleController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_role_index',
                        'mauticContent' => 'role',
                    ],
                ]);
            }
            if ($valid) {
                return $this->editAction($request, $entity->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'              => $form->createView(),
                'permissionsConfig' => $permissionsConfig,
            ],
            'contentTemplate' => self::TEMPLATE_FORM,
            'passthroughVars' => [
                'activeLink'     => '#mautic_role_new',
                'route'          => $this->generateUrl('mautic_role_action', ['objectAction' => 'new']),
                'mauticContent'  => 'role',
                'permissionList' => $permissionsConfig['list'],
            ],
        ]);
    }

    /**
     * Clone an existing role and present it as a new form.
     */
    public function cloneAction(Request $request, int $objectId, RoleModel $model): Response
    {
        if (!$this->security->isGranted(self::PERMISSION_CREATE) || !$this->security->isGranted(self::PERMISSION_VIEW)) {
            $this->throwAccessDenied();
        }

        $source         = $model->getEntity($objectId);
        $postActionVars = $this->getRoleClonePostActionVars($request);

        if (null === $source) {
            return $this->getRoleNotFoundResponse($postActionVars, $objectId);
        }

        return $this->handleRoleClone($request, $objectId, $source, $model, $postActionVars);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRoleClonePostActionVars(Request $request): array
    {
        $page      = $request->getSession()->get('mautic.role.page', 1);
        $returnUrl = $this->generateUrl('mautic_role_index', ['page' => $page]);

        return [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\\UserBundle\\Controller\\RoleController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'mauticContent' => 'role',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function getRoleNotFoundResponse(array $postActionVars, int $objectId): Response
    {
        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.role.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ])
        );
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function handleRoleClone(Request $request, int $objectId, Entity\Role $source, RoleModel $model, array $postActionVars): Response
    {
        $entity            = $model->cloneEntity($source);
        $permissionsConfig = $this->getPermissionsConfig($source);
        $action            = $this->generateUrl('mautic_role_action', ['objectAction' => 'clone', 'objectId' => $objectId]);
        $form              = $model->createForm($entity, $this->formFactory, $action, ['permissionsConfig' => $permissionsConfig['config']]);
        if (!$request->isMethod('POST')) {
            return $this->renderRoleCloneForm($form, $permissionsConfig, $action);
        }

        $response = $this->handleRoleClonePost($request, $entity, $model, $form, $postActionVars);

        return $response ?? $this->renderRoleCloneForm($form, $permissionsConfig, $action);
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function handleRoleClonePost(Request $request, Entity\Role $entity, RoleModel $model, FormInterface $form, array $postActionVars): ?Response
    {
        $cancelled = $this->isFormCancelled($form);
        $valid     = !$cancelled && $this->isFormValid($form);

        if ($valid) {
            $role        = $request->request->all()['role'] ?? [];
            $permissions = $role['permissions'] ?? null;

            if (null !== $permissions) {
                $model->setRolePermissions($entity, $permissions);
            }

            $model->saveEntity($entity);

            $this->addFlashMessage('mautic.core.notice.created', [
                '%name%'              => $entity->getName(),
                self::FLASH_MENU_LINK => 'mautic_role_index',
                self::FLASH_URL       => $this->generateUrl('mautic_role_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                ]),
            ]);
        }

        if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
            return $this->postActionRedirect($postActionVars);
        }

        if ($valid) {
            return $this->editAction($request, $entity->getId(), true);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $permissionsConfig
     */
    private function renderRoleCloneForm(FormInterface $form, array $permissionsConfig, string $action): Response
    {
        return $this->delegateView([
            'viewParameters' => [
                'form'              => $form->createView(),
                'permissionsConfig' => $permissionsConfig,
            ],
            'contentTemplate' => self::TEMPLATE_FORM,
            'passthroughVars' => [
                'activeLink'     => '#mautic_role_new',
                'route'          => $action,
                'mauticContent'  => 'role',
                'permissionList' => $permissionsConfig['list'],
            ],
        ]);
    }

    /**
     * Generate's role edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        if (!$this->security->isGranted(self::PERMISSION_EDIT)) {
            $this->throwAccessDenied();
        }
        $entity = $this->roleModel->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.role.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_role_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\UserBundle\Controller\RoleController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'mauticContent' => 'role',
            ],
        ];

        // user not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.user.role.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }
        if ($this->roleModel->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'user.role');
        }

        $permissionsConfig = $this->getPermissionsConfig($entity);
        $action            = $this->generateUrl('mautic_role_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form              = $this->roleModel->createForm($entity, $this->formFactory, $action, ['permissionsConfig' => $permissionsConfig['config']]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // set the permissions
                    $role        = $request->request->all()['role'] ?? [];
                    $permissions = $role['permissions'] ?? null;
                    $this->roleModel->setRolePermissions($entity, $permissions);

                    // form is valid so process the data
                    $this->roleModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'              => $entity->getName(),
                        self::FLASH_MENU_LINK => 'mautic_role_index',
                        self::FLASH_URL       => $this->generateUrl('mautic_role_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            } else {
                // unlock the entity
                $this->roleModel->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            }
            // the form has to be rebuilt because the permissions were updated
            $permissionsConfig = $this->getPermissionsConfig($entity);
            $form              = $this->roleModel->createForm($entity, $this->formFactory, $action, ['permissionsConfig' => $permissionsConfig['config']]);
        } else {
            // lock the entity
            $this->roleModel->lockEntity($entity);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'              => $form->createView(),
                'permissionsConfig' => $permissionsConfig,
            ],
            'contentTemplate' => self::TEMPLATE_FORM,
            'passthroughVars' => [
                'activeLink'     => '#mautic_role_index',
                'route'          => $action,
                'mauticContent'  => 'role',
                'permissionList' => $permissionsConfig['list'],
            ],
        ]);
    }

    private function getPermissionsConfig(Entity\Role $role): array
    {
        $permissionObjects = $this->security->getPermissionObjects();
        $permissionRepo    = $this->doctrine->getRepository(Entity\Permission::class);
        \assert($permissionRepo instanceof PermissionRepository);

        $permissionsArray = ($role->getId()) ? $permissionRepo->getPermissionsByRole($role, true) : [];

        $permissions     = [];
        $permissionsList = [];
        /** @var \Mautic\CoreBundle\Security\Permissions\AbstractPermissions $object */
        foreach ($permissionObjects as $object) {
            if (!is_object($object)) {
                continue;
            }

            if ($object->isEnabled()) {
                $bundle = $object->getName();
                $label  = $this->translator->trans("mautic.{$bundle}.permissions.header");

                // convert the permission bits from the db into readable names
                $data = $object->convertBitsToPermissionNames($permissionsArray);

                // get the ratio of granted/total
                [$granted, $total] = $object->getPermissionRatio($data);

                $permissions[$bundle] = [
                    'label'            => $label,
                    'permissionObject' => $object,
                    'ratio'            => [$granted, $total],
                    'data'             => $data,
                ];

                $perms = $object->getPermissions();
                foreach ($perms as $level => $perm) {
                    $levelPerms = array_keys($perm);
                    $object->parseForJavascript($levelPerms);
                    $permissionsList[$bundle][$level] = $levelPerms;
                }
            }
        }

        // order permissions by label
        uasort($permissions, fn ($a, $b): int => strnatcmp($a['label'], $b['label']));

        return ['config' => $permissions, 'list' => $permissionsList];
    }

    /**
     * Delete's a role.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        if (!$this->security->isGranted(self::PERMISSION_DELETE)) {
            $this->throwAccessDenied();
        }

        $page           = $request->getSession()->get('mautic.role.page', 1);
        $returnUrl      = $this->generateUrl('mautic_role_index', ['page' => $page]);
        $success        = 0;
        $flashes        = [];
        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\UserBundle\Controller\RoleController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'success'       => $success,
                'mauticContent' => 'role',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            try {
                $entity = $this->roleModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.role.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif ($this->roleModel->isLocked($entity)) {
                    return $this->isLocked($postActionVars, $entity, 'user.role');
                } else {
                    $this->roleModel->deleteEntity($entity);
                    $name      = $entity->getName();
                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.core.notice.deleted',
                        'msgVars' => [
                            '%name%' => $name,
                            '%id%'   => $objectId,
                        ],
                    ];
                }
            } catch (PreconditionRequiredHttpException $e) {
                $flashes[] = [
                    'type' => 'error',
                    'msg'  => $e->getMessage(),
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request, RoleModel $model): Response
    {
        $page      = $request->getSession()->get('mautic.role.page', 1);
        $returnUrl = $this->generateUrl('mautic_role_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\UserBundle\Controller\RoleController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'mauticContent' => 'role',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $ids       = json_decode($request->query->get('ids', ''));
            $deleteIds = [];
            $userRepo  = $this->doctrine->getRepository(Entity\User::class);
            \assert($userRepo instanceof UserRepository);

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);
                $users  = $userRepo->findByRole($entity);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.role.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (count($users)) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.role.error.deletenotallowed',
                        'msgVars' => ['%name%' => $entity->getName()],
                    ];
                } elseif (!$this->security->isGranted(self::PERMISSION_DELETE)) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'user.role', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.user.role.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
