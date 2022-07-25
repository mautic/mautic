<?php

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\UserBundle\Entity as Entity;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

class RoleController extends FormController
{
    /**
     * Generate's default role list view.
     *
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:view')) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        /** @var PageHelperFactoryInterface $pageHelperFacotry */
        $pageHelperFacotry = $this->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFacotry->make('mautic.role', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $orderBy    = $this->get('session')->get('mautic.role.orderby', 'r.name');
        $orderByDir = $this->get('session')->get('mautic.role.orderbydir', 'ASC');
        $filter     = $this->request->get('search', $this->get('session')->get('mautic.role.filter', ''));
        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        $model      = $this->getModel('user.role');
        $items      = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $this->get('session')->set('mautic.role.filter', $filter);

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
                'contentTemplate' => 'MauticUserBundle:Role:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_role_index',
                    'mauticContent' => 'role',
                ],
            ]);
        }

        $roleIds = [];

        foreach ($items as $role) {
            $roleIds[] = $role->getId();
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView([
            'viewParameters'  => [
                'items'       => $items,
                'userCounts'  => (!empty($roleIds)) ? $model->getRepository()->getUserCount($roleIds) : [],
                'searchValue' => $filter,
                'page'        => $page,
                'limit'       => $limit,
                'tmpl'        => $tmpl,
                'permissions' => [
                    'create' => $this->get('mautic.security')->isGranted('user:roles:create'),
                    'edit'   => $this->get('mautic.security')->isGranted('user:roles:edit'),
                    'delete' => $this->get('mautic.security')->isGranted('user:roles:delete'),
                ],
            ],
            'contentTemplate' => 'MauticUserBundle:Role:list.html.php',
            'passthroughVars' => [
                'route'         => $this->generateUrl('mautic_role_index', ['page' => $page]),
                'mauticContent' => 'role',
            ],
        ]);
    }

    /**
     * Generate's new role form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:create')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $entity = new Entity\Role();
        $model  = $this->getModel('user.role');

        //set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_role_index');

        //set the page we came from
        $page   = $this->get('session')->get('mautic.role.page', 1);
        $action = $this->generateUrl('mautic_role_action', ['objectAction' => 'new']);

        //get the user form factory
        $permissionsConfig = $this->getPermissionsConfig($entity);
        $form              = $model->createForm($entity, $this->get('form.factory'), $action, ['permissionsConfig' => $permissionsConfig['config']]);

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //set the permissions
                    $role        = $this->request->request->get('role', []);
                    $permissions = $role['permissions'] ?? null;
                    $model->setRolePermissions($entity, $permissions);

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash('mautic.core.notice.created', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_role_index',
                        '%url%'       => $this->generateUrl('mautic_role_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticUserBundle:Role:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_role_index',
                        'mauticContent' => 'role',
                    ],
                ]);
            } else {
                return $this->editAction($entity->getId());
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'              => $this->setFormTheme($form, 'MauticUserBundle:Role:form.html.php', 'MauticUserBundle:FormTheme\Role'),
                'permissionsConfig' => $permissionsConfig,
            ],
            'contentTemplate' => 'MauticUserBundle:Role:form.html.php',
            'passthroughVars' => [
                'activeLink'     => '#mautic_role_new',
                'route'          => $this->generateUrl('mautic_role_action', ['objectAction' => 'new']),
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = true)
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:edit')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\UserBundle\Model\RoleModel $model */
        $model  = $this->getModel('user.role');
        $entity = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.role.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_role_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticUserBundle:Role:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'mauticContent' => 'role',
            ],
        ];

        //user not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.user.role.error.notfound',
                            'msgVars' => ['%id' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'user.role');
        }

        $permissionsConfig = $this->getPermissionsConfig($entity);
        $action            = $this->generateUrl('mautic_role_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form              = $model->createForm($entity, $this->get('form.factory'), $action, ['permissionsConfig' => $permissionsConfig['config']]);

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $this->request->getMethod()) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //set the permissions
                    $role        = $this->request->request->get('role', []);
                    $permissions = $role['permissions'] ?? null;
                    $model->setRolePermissions($entity, $permissions);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash('mautic.core.notice.updated', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_role_index',
                        '%url%'       => $this->generateUrl('mautic_role_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            } else {
                //the form has to be rebuilt because the permissions were updated
                $permissionsConfig = $this->getPermissionsConfig($entity);
                $form              = $model->createForm($entity, $this->get('form.factory'), $action, ['permissionsConfig' => $permissionsConfig['config']]);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'              => $this->setFormTheme($form, 'MauticUserBundle:Role:form.html.php', 'MauticUserBundle:FormTheme\Role'),
                'permissionsConfig' => $permissionsConfig,
            ],
            'contentTemplate' => 'MauticUserBundle:Role:form.html.php',
            'passthroughVars' => [
                'activeLink'     => '#mautic_role_index',
                'route'          => $action,
                'mauticContent'  => 'role',
                'permissionList' => $permissionsConfig['list'],
            ],
        ]);
    }

    /**
     * @return array
     */
    private function getPermissionsConfig(Entity\Role $role)
    {
        $permissionObjects = $this->get('mautic.security')->getPermissionObjects();
        $translator        = $this->translator;

        $permissionsArray = ($role->getId()) ?
            $this->get('doctrine.orm.entity_manager')->getRepository('MauticUserBundle:Permission')->getPermissionsByRole($role, true) :
            [];

        $permissions     = [];
        $permissionsList = [];
        /** @var \Mautic\CoreBundle\Security\Permissions\AbstractPermissions $object */
        foreach ($permissionObjects as $object) {
            if (!is_object($object)) {
                continue;
            }

            if ($object->isEnabled()) {
                $bundle = $object->getName();
                $label  = $translator->trans("mautic.{$bundle}.permissions.header");

                //convert the permission bits from the db into readable names
                $data = $object->convertBitsToPermissionNames($permissionsArray);

                //get the ratio of granted/total
                list($granted, $total) = $object->getPermissionRatio($data);

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

        //order permissions by label
        uasort($permissions, function ($a, $b) {
            return strnatcmp($a['label'], $b['label']);
        });

        return ['config' => $permissions, 'list' => $permissionsList];
    }

    /**
     * Delete's a role.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:delete')) {
            return $this->accessDenied();
        }

        $page           = $this->get('session')->get('mautic.role.page', 1);
        $returnUrl      = $this->generateUrl('mautic_role_index', ['page' => $page]);
        $success        = 0;
        $flashes        = [];
        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticUserBundle:Role:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'success'       => $success,
                'mauticContent' => 'role',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            try {
                $model  = $this->getModel('user.role');
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.role.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif ($model->isLocked($entity)) {
                    return $this->isLocked($postActionVars, $entity, 'user.role');
                } else {
                    $model->deleteEntity($entity);
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
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.role.page', 1);
        $returnUrl = $this->generateUrl('mautic_role_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticUserBundle:Role:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_role_index',
                'mauticContent' => 'role',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model       = $this->getModel('user.role');
            $ids         = json_decode($this->request->query->get('ids', ''));
            $deleteIds   = [];
            $currentUser = $this->user;

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);
                $users  = $this->get('doctrine.orm.entity_manager')->getRepository('MauticUserBundle:User')->findByRole($entity);

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
                } elseif (!$this->get('mautic.security')->isGranted('user:roles:delete')) {
                    $flashes[] = $this->accessDenied(true);
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
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
