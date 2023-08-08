<?php

namespace Mautic\UserBundle\Controller;

use JMS\Serializer\SerializerInterface;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Form\Type\ContactType;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends FormController
{
    /**
     * Generate's default user list.
     *
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        if (!$this->security->isGranted('user:users:view')) {
            return $this->accessDenied();
        }

        $pageHelper = $pageHelperFactory->make('mautic.user', $page);

        $this->setListFilters();

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $orderBy    = $request->getSession()->get('mautic.user.orderby', 'u.lastName, u.firstName, u.username');
        $orderByDir = $request->getSession()->get('mautic.user.orderbydir', 'ASC');
        $search     = $request->get('search', $request->getSession()->get('mautic.user.filter', ''));
        $search     = html_entity_decode($search);
        $request->getSession()->set('mautic.user.filter', $search);

        // do some default filtering
        $filter = ['string' => $search, 'force' => ''];
        $tmpl   = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';
        $users  = $this->getModel('user.user')->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]);

        // Check to see if the number of pages match the number of users
        $count = count($users);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            $lastPage = $pageHelper->countPage($count);
            $pageHelper->rememberPage($lastPage);
            $returnUrl = $this->generateUrl('mautic_user_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => 'Mautic\UserBundle\Controller\UserController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_user_index',
                    'mauticContent' => 'user',
                ],
            ]);
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView([
            'viewParameters'  => [
                'items'       => $users,
                'searchValue' => $search,
                'page'        => $page,
                'limit'       => $limit,
                'tmpl'        => $tmpl,
                'permissions' => [
                    'create' => $this->security->isGranted('user:users:create'),
                    'edit'   => $this->security->isGranted('user:users:editother'),
                    'delete' => $this->security->isGranted('user:users:deleteother'),
                ],
            ],
            'contentTemplate' => '@MauticUser/User/list.html.twig',
            'passthroughVars' => [
                'route'         => $this->generateUrl('mautic_user_index', ['page' => $page]),
                'mauticContent' => 'user',
            ],
        ]);
    }

    /**
     * Generate's form and processes new post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, LanguageHelper $languageHelper, UserPasswordHasherInterface $hasher)
    {
        if (!$this->security->isGranted('user:users:create')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model = $this->getModel('user.user');

        // retrieve the user entity
        $user = $model->getEntity();

        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_user_index');

        // set the page we came from
        $page = $request->getSession()->get('mautic.user.page', 1);

        // get the user form factory
        $action = $this->generateUrl('mautic_user_action', ['objectAction' => 'new']);
        $form   = $model->createForm($user, $this->formFactory, $action);

        // Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                // check to see if the password needs to be rehashed
                $formUser          = $request->request->get('user') ?? [];
                $submittedPassword = $formUser['plainPassword']['password'] ?? null;
                $password          = $model->checkNewPassword($user, $hasher, $submittedPassword);

                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $user->setPassword($password);
                    $model->saveEntity($user);

                    // check if the user's locale has been downloaded already, fetch it if not
                    $installedLanguages = $languageHelper->getSupportedLanguages();

                    if ($user->getLocale() && !array_key_exists($user->getLocale(), $installedLanguages)) {
                        $fetchLanguage = $languageHelper->extractLanguagePackage($user->getLocale());

                        // If there is an error, we need to reset the user's locale to the default
                        if ($fetchLanguage['error']) {
                            $user->setLocale(null);
                            $model->saveEntity($user);
                            $message     = 'mautic.core.could.not.set.language';
                            $messageVars = [];

                            if (isset($fetchLanguage['message'])) {
                                $message = $fetchLanguage['message'];
                            }

                            if (isset($fetchLanguage['vars'])) {
                                $messageVars = $fetchLanguage['vars'];
                            }

                            $this->addFlashMessage($message, $messageVars);
                        }
                    }

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $user->getName(),
                        '%menu_link%' => 'mautic_user_index',
                        '%url%'       => $this->generateUrl('mautic_user_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $user->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\UserBundle\Controller\UserController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_user_index',
                        'mauticContent' => 'user',
                    ],
                ]);
            } elseif ($valid && !$cancelled) {
                return $this->editAction($request, $languageHelper, $hasher, $user->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters'  => ['form' => $form->createView()],
            'contentTemplate' => '@MauticUser/User/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_new',
                'route'         => $action,
                'mauticContent' => 'user',
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LanguageHelper $languageHelper, UserPasswordHasherInterface $hasher, $objectId, $ignorePost = false)
    {
        if (!$this->security->isGranted('user:users:edit')) {
            return $this->accessDenied();
        }
        $model = $this->getModel('user.user');
        \assert($model instanceof UserModel);
        $user = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.user.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_user_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\UserBundle\Controller\UserController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'mauticContent' => 'user',
            ],
        ];

        if (null === $user) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.user.user.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif ($model->isLocked($user)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $user, 'user.user');
        }

        $action = $this->generateUrl('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($user, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                // check to see if the password needs to be rehashed
                $formUser          = $request->request->get('user') ?? [];
                $submittedPassword = $formUser['plainPassword']['password'] ?? null;
                $password          = $model->checkNewPassword($user, $hasher, $submittedPassword);

                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $user->setPassword($password);
                    $model->saveEntity($user, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    // check if the user's locale has been downloaded already, fetch it if not
                    $installedLanguages = $languageHelper->getSupportedLanguages();

                    if ($user->getLocale() && !array_key_exists($user->getLocale(), $installedLanguages)) {
                        $fetchLanguage = $languageHelper->extractLanguagePackage($user->getLocale());

                        // If there is an error, we need to reset the user's locale to the default
                        if ($fetchLanguage['error']) {
                            $user->setLocale(null);
                            $model->saveEntity($user);
                            $message     = 'mautic.core.could.not.set.language';
                            $messageVars = [];

                            if (isset($fetchLanguage['message'])) {
                                $message = $fetchLanguage['message'];
                            }

                            if (isset($fetchLanguage['vars'])) {
                                $messageVars = $fetchLanguage['vars'];
                            }

                            $this->addFlashMessage($message, $messageVars);
                        }
                    }

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $user->getName(),
                        '%menu_link%' => 'mautic_user_index',
                        '%url%'       => $this->generateUrl('mautic_user_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $user->getId(),
                        ]),
                    ]);
                }
            } else {
                // unlock the entity
                $model->unlockEntity($user);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            // lock the entity
            $model->lockEntity($user);
        }

        return $this->delegateView([
            'viewParameters'  => ['form' => $form->createView()],
            'contentTemplate' => '@MauticUser/User/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'route'         => $action,
                'mauticContent' => 'user',
            ],
        ]);
    }

    /**
     * Deletes a user object.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        if (!$this->security->isGranted('user:users:delete')) {
            return $this->accessDenied();
        }

        $currentUser    = $this->user;
        $page           = $request->getSession()->get('mautic.user.page', 1);
        $returnUrl      = $this->generateUrl('mautic_user_index', ['page' => $page]);
        $success        = 0;
        $flashes        = [];
        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\UserBundle\Controller\UserController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'route'         => $returnUrl,
                'success'       => $success,
                'mauticContent' => 'user',
            ],
        ];
        if ('POST' === $request->getMethod()) {
            // ensure the user logged in is not getting deleted
            if ((int) $currentUser->getId() !== (int) $objectId) {
                $model = $this->getModel('user.user');
                \assert($model instanceof UserModel);
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.user.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif ($model->isLocked($entity)) {
                    return $this->isLocked($postActionVars, $entity, 'user.user');
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
            } else {
                $flashes[] = [
                    'type' => 'error',
                    'msg'  => 'mautic.user.user.error.cannotdeleteself',
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
     * Contacts a user.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function contactAction(Request $request, SerializerInterface $serializer, MailHelper $mailer, $objectId)
    {
        $model = $this->getModel('user.user');
        $user  = $model->getEntity($objectId);

        // user not found
        if (null === $user) {
            return $this->postActionRedirect([
                'returnUrl'       => $this->generateUrl('mautic_dashboard_index'),
                'contentTemplate' => 'Mautic\UserBundle\Controller\UserController::contactAction',
                'flashes'         => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.user.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        }

        $action = $this->generateUrl('mautic_user_action', ['objectAction' => 'contact', 'objectId' => $objectId]);
        $form   = $this->createForm(ContactType::class, [], ['action' => $action]);

        $currentUser = $this->user;

        if ('POST' === $request->getMethod()) {
            $contact   = $request->request->get('contact') ?? [];
            $formUrl   = $contact['returnUrl'] ?? '';
            $returnUrl = $formUrl ? urldecode($formUrl) : $this->generateUrl('mautic_dashboard_index');
            $valid     = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $subject = InputHelper::clean($form->get('msg_subject')->getData());
                    $body    = InputHelper::clean($form->get('msg_body')->getData());

                    $mailer->setFrom($currentUser->getEmail(), $currentUser->getName());
                    $mailer->setSubject($subject);
                    $mailer->setTo($user->getEmail(), $user->getName());
                    $mailer->setBody($body);
                    $mailer->send();

                    $reEntity = $form->get('entity')->getData();
                    if (empty($reEntity)) {
                        $bundle   = $object   = 'user';
                        $entityId = $user->getId();
                    } else {
                        $bundle = $object = $reEntity;
                        if (strpos($reEntity, ':')) {
                            [$bundle, $object] = explode(':', $reEntity);
                        }
                        $entityId = $form->get('id')->getData();
                    }

                    $details = $serializer->serialize([
                        'from'    => $currentUser->getName(),
                        'to'      => $user->getName(),
                        'subject' => $subject,
                        'message' => $body,
                    ], 'json');

                    $log = [
                        'bundle'    => $bundle,
                        'object'    => $object,
                        'objectId'  => $entityId,
                        'action'    => 'communication',
                        'details'   => $details,
                        'ipAddress' => $this->factory->getIpAddressFromRequest(),
                    ];
                    $auditLogModel = $this->getModel('core.auditlog');
                    \assert($auditLogModel instanceof AuditLogModel);
                    $auditLogModel->writeToLog($log);

                    $this->addFlashMessage('mautic.user.user.notice.messagesent', ['%name%' => $user->getName()]);
                }
            }
            if ($cancelled || $valid) {
                return $this->redirect($returnUrl);
            }
        } else {
            $reEntityId = (int) $request->get('id');
            $reSubject  = InputHelper::clean($request->get('subject'));
            $returnUrl  = InputHelper::clean($request->get('returnUrl', $this->generateUrl('mautic_dashboard_index')));
            $reEntity   = InputHelper::clean($request->get('entity'));

            $form->get('entity')->setData($reEntity);
            $form->get('id')->setData($reEntityId);
            $form->get('returnUrl')->setData($returnUrl);

            if (!empty($reEntity) && !empty($reEntityId)) {
                /** @var FormModel<object> $model */
                $model  = $this->getModel($reEntity);
                $entity = $model->getEntity($reEntityId);

                if (null !== $entity) {
                    $subject = $model->getUserContactSubject($reSubject, $entity);
                    $form->get('msg_subject')->setData($subject);
                }
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'user' => $user,
            ],
            'contentTemplate' => '@MauticUser/User/contact.html.twig',
            'passthroughVars' => [
                'route'         => $action,
                'mauticContent' => 'user',
            ],
        ]);
    }

    /**
     * Deletes a group of entities.
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.user.page', 1);
        $returnUrl = $this->generateUrl('mautic_user_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\UserBundle\Controller\UserController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'mauticContent' => 'user',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('user');
            \assert($model instanceof UserModel);
            $ids         = json_decode($request->query->get('ids', ''));
            $deleteIds   = [];
            $currentUser = $this->user;

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ((int) $currentUser->getId() === (int) $objectId) {
                    $flashes[] = [
                        'type' => 'error',
                        'msg'  => 'mautic.user.user.error.cannotdeleteself',
                    ];
                } elseif (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.user.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted('user:users:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'user', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.user.user.notice.batch_deleted',
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
