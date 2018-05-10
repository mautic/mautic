<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\UserBundle\Form\Type as FormType;

/**
 * Class UserController.
 */
class UserController extends FormController
{
    /**
     * Generate's default user list.
     *
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('user:users:view')) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.user.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get('mautic.user.orderby', 'u.lastName, u.firstName, u.username');
        $orderByDir = $this->get('session')->get('mautic.user.orderbydir', 'ASC');

        $search = $this->request->get('search', $this->get('session')->get('mautic.user.filter', ''));
        $this->get('session')->set('mautic.user.filter', $search);

        //do some default filtering
        $filter = ['string' => $search, 'force' => ''];

        $tmpl  = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        $users = $this->getModel('user.user')->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]);

        //Check to see if the number of pages match the number of users
        $count = count($users);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->get('session')->set('mautic.user.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_user_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => 'MauticUserBundle:User:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_user_index',
                    'mauticContent' => 'user',
                ],
            ]);
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.user.page', $page);

        //set some permissions
        $permissions = [
            'create' => $this->get('mautic.security')->isGranted('user:users:create'),
            'edit'   => $this->get('mautic.security')->isGranted('user:users:editother'),
            'delete' => $this->get('mautic.security')->isGranted('user:users:deleteother'),
        ];

        $parameters = [
            'items'       => $users,
            'searchValue' => $search,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'tmpl'        => $tmpl,
        ];

        return $this->delegateView([
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticUserBundle:User:list.html.php',
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
    public function newAction()
    {
        if (!$this->get('mautic.security')->isGranted('user:users:create')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model = $this->getModel('user.user');

        //retrieve the user entity
        $user = $model->getEntity();

        //set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_user_index');

        //set the page we came from
        $page = $this->get('session')->get('mautic.user.page', 1);

        //get the user form factory
        $action = $this->generateUrl('mautic_user_action', ['objectAction' => 'new']);
        $form   = $model->createForm($user, $this->get('form.factory'), $action);

        //Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                //check to see if the password needs to be rehashed
                $submittedPassword = $this->request->request->get('user[plainPassword][password]', null, true);
                $encoder           = $this->get('security.encoder_factory')->getEncoder($user);
                $password          = $model->checkNewPassword($user, $encoder, $submittedPassword);

                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $user->setPassword($password);
                    $model->saveEntity($user);

                    //check if the user's locale has been downloaded already, fetch it if not
                    $installedLanguages = $this->coreParametersHelper->getParameter('supported_languages');

                    if ($user->getLocale() && !array_key_exists($user->getLocale(), $installedLanguages)) {
                        /** @var \Mautic\CoreBundle\Helper\LanguageHelper $languageHelper */
                        $languageHelper = $this->factory->getHelper('language');

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

                            $this->addFlash($message, $messageVars);
                        }
                    }

                    $this->addFlash('mautic.core.notice.created', [
                        '%name%'      => $user->getName(),
                        '%menu_link%' => 'mautic_user_index',
                        '%url%'       => $this->generateUrl('mautic_user_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $user->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticUserBundle:User:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_user_index',
                        'mauticContent' => 'user',
                    ],
                ]);
            } elseif ($valid && !$cancelled) {
                return $this->editAction($user->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters'  => ['form' => $form->createView()],
            'contentTemplate' => 'MauticUserBundle:User:form.html.php',
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
    public function editAction($objectId, $ignorePost = false)
    {
        if (!$this->get('mautic.security')->isGranted('user:users:edit')) {
            return $this->accessDenied();
        }
        $model = $this->getModel('user.user');
        $user  = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.user.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_user_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticUserBundle:User:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'mauticContent' => 'user',
            ],
        ];

        //user not found
        if ($user === null) {
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
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $user, 'user.user');
        }

        $action = $this->generateUrl('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($user, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                //check to see if the password needs to be rehashed
                $submittedPassword = $this->request->request->get('user[plainPassword][password]', null, true);
                $encoder           = $this->get('security.encoder_factory')->getEncoder($user);
                $password          = $model->checkNewPassword($user, $encoder, $submittedPassword);

                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $user->setPassword($password);
                    $model->saveEntity($user, $form->get('buttons')->get('save')->isClicked());

                    //check if the user's locale has been downloaded already, fetch it if not
                    $installedLanguages = $this->coreParametersHelper->getParameter('supported_languages');

                    if ($user->getLocale() && !array_key_exists($user->getLocale(), $installedLanguages)) {
                        /** @var \Mautic\CoreBundle\Helper\LanguageHelper $languageHelper */
                        $languageHelper = $this->factory->getHelper('language');

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

                            $this->addFlash($message, $messageVars);
                        }
                    }

                    $this->addFlash('mautic.core.notice.updated', [
                        '%name%'      => $user->getName(),
                        '%menu_link%' => 'mautic_user_index',
                        '%url%'       => $this->generateUrl('mautic_user_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $user->getId(),
                        ]),
                    ]);
                }
            } else {
                //unlock the entity
                $model->unlockEntity($user);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            //lock the entity
            $model->lockEntity($user);
        }

        return $this->delegateView([
            'viewParameters'  => ['form' => $form->createView()],
            'contentTemplate' => 'MauticUserBundle:User:form.html.php',
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('user:users:delete')) {
            return $this->accessDenied();
        }

        $currentUser    = $this->user;
        $page           = $this->get('session')->get('mautic.user.page', 1);
        $returnUrl      = $this->generateUrl('mautic_user_index', ['page' => $page]);
        $success        = 0;
        $flashes        = [];
        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticUserBundle:User:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'route'         => $returnUrl,
                'success'       => $success,
                'mauticContent' => 'user',
            ],
        ];
        if ($this->request->getMethod() == 'POST') {
            //ensure the user logged in is not getting deleted
            if ((int) $currentUser->getId() !== (int) $objectId) {
                $model  = $this->getModel('user.user');
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
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
        } //else don't do anything

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function contactAction($objectId)
    {
        $model = $this->getModel('user.user');
        $user  = $model->getEntity($objectId);

        //user not found
        if ($user === null) {
            return $this->postActionRedirect([
                'returnUrl'       => $this->generateUrl('mautic_dashboard_index'),
                'contentTemplate' => 'MauticUserBundle:User:contact',
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
        $form   = $this->createForm(new FormType\ContactType(), [], ['action' => $action]);

        $currentUser = $this->user;

        if ($this->request->getMethod() == 'POST') {
            $formUrl   = $this->request->request->get('contact[returnUrl]', '', true);
            $returnUrl = ($formUrl) ? urldecode($formUrl) : $this->generateUrl('mautic_dashboard_index');
            $valid     = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $subject = InputHelper::clean($form->get('msg_subject')->getData());
                    $body    = InputHelper::clean($form->get('msg_body')->getData());
                    $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom($currentUser->getEmail(), $currentUser->getName())
                        ->setTo($user->getEmail(), $user->getName())
                        ->setBody($body);
                    $this->get('mailer')->send($message);

                    $reEntity = $form->get('entity')->getData();
                    if (empty($reEntity)) {
                        $bundle   = $object   = 'user';
                        $entityId = $user->getId();
                    } else {
                        $bundle = $object = $reEntity;
                        if (strpos($reEntity, ':')) {
                            list($bundle, $object) = explode(':', $reEntity);
                        }
                        $entityId = $form->get('id')->getData();
                    }

                    $serializer = $this->get('jms_serializer');
                    $details    = $serializer->serialize([
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
                    $this->getModel('core.auditLog')->writeToLog($log);

                    $this->addFlash('mautic.user.user.notice.messagesent', ['%name%' => $user->getName()]);
                }
            }
            if ($cancelled || $valid) {
                return $this->redirect($returnUrl);
            }
        } else {
            $reEntityId = InputHelper::int($this->request->get('id'));
            $reSubject  = InputHelper::clean($this->request->get('subject'));
            $returnUrl  = InputHelper::clean($this->request->get('returnUrl', $this->generateUrl('mautic_dashboard_index')));
            $reEntity   = InputHelper::clean($this->request->get('entity'));

            $form->get('entity')->setData($reEntity);
            $form->get('id')->setData($reEntityId);
            $form->get('returnUrl')->setData($returnUrl);

            if (!empty($reEntity) && !empty($reEntityId)) {
                $model  = $this->getModel($reEntity);
                $entity = $model->getEntity($reEntityId);

                if ($entity !== null) {
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
            'contentTemplate' => 'MauticUserBundle:User:contact.html.php',
            'passthroughVars' => [
                'route'         => $action,
                'mauticContent' => 'user',
            ],
        ]);
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.user.page', 1);
        $returnUrl = $this->generateUrl('mautic_user_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticUserBundle:User:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_user_index',
                'mauticContent' => 'user',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model       = $this->getModel('user');
            $ids         = json_decode($this->request->query->get('ids', ''));
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
                } elseif ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.user.user.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->isGranted('user:users:delete')) {
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
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
