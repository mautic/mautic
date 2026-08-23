<?php

namespace Mautic\SmsBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class SmsController extends FormController
{
    use EntityContactsTrait;

    private AuditLogModel $auditLogModel;

    private SmsModel $smsModel;

    #[Required]
    public function autowireSmsController(
        SmsModel $smsModel,
        AuditLogModel $auditLogModel,
    ): void {
        $this->smsModel = $smsModel;
        $this->auditLogModel = $auditLogModel;
    }

    #[Route('/s/sms/{objectAction}/{objectId}', name: 'mautic_sms_action', requirements: ['objectId' => '[a-zA-Z0-9_-]+'], defaults: ['objectId' => 0])]
    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = ''): Response
    {
        return parent::executeAction($request, $objectAction, $objectId, $objectSubId, $objectModel);
    }

    /**
     * @param int $page
     */
    #[Route('/s/sms/{page}', name: 'mautic_sms_index', requirements: ['page' => '\d+'], defaults: ['page' => 0])]
    public function indexAction(Request $request, TransportChain $transportChain, $page = 1): Response
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'sms:smses:viewown',
                'sms:smses:viewother',
                'sms:smses:create',
                'sms:smses:editown',
                'sms:smses:editother',
                'sms:smses:deleteown',
                'sms:smses:deleteother',
                'sms:smses:publishown',
                'sms:smses:publishother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['sms:smses:viewown'] && !$permissions['sms:smses:viewother']) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();

        // set limits
        $limit = $session->get('mautic.sms.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.sms.filter', ''));
        $session->set('mautic.sms.filter', $search);

        $filter = ['string' => $search];

        if (!$permissions['sms:smses:viewother']) {
            $filter['force'][] =
                [
                    'column' => 'e.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->user->getId(),
                ];
        }

        // Not to include translations
        $filter['force'][] = ['column' => 'e.translationParent', 'expr' => 'isNull'];

        $orderBy    = $session->get('mautic.sms.orderby', 'e.name');
        $orderByDir = $session->get('mautic.sms.orderbydir', $this->getDefaultOrderDirection());

        $smss = $this->smsModel->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => $filter,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);

        $count = count($smss);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($count / $limit)) ?: 1;
            }

            $session->set('mautic.sms.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                ],
            ]);
        }
        $session->set('mautic.sms.page', $page);

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $smss,
                'totalItems'  => $count,
                'page'        => $page,
                'limit'       => $limit,
                'tmpl'        => $request->get('tmpl', 'index'),
                'permissions' => $permissions,
                'model'       => $this->smsModel,
                'security'    => $this->security,
                'configured'  => count($transportChain->getEnabledTransports()) > 0,
            ],
            'contentTemplate' => '@MauticSms/Sms/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sms_index',
                'mauticContent' => 'sms',
                'route'         => $this->generateUrl('mautic_sms_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Loads a specific form into the detailed panel.
     */
    public function viewAction(Request $request, $objectId): Response
    {
        $security = $this->security;

        /** @var Sms $sms */
        $sms = $this->smsModel->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.sms.page', 1);

        if (null === $sms) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.sms.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        }
        if (!$this->security->hasEntityAccess(
            'sms:smses:viewown',
            'sms:smses:viewother',
            $sms->getCreatedBy()
        )
        ) {
            $this->throwAccessDenied();
        }
        $logs = $this->auditLogModel->getLogForObject('sms', $sms->getId(), $sms->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action          = $this->generateUrl('mautic_sms_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        $entityViews     = $this->smsModel->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['sms_id' => $sms->getId()]
        );

        // Get click through stats
        $trackableLinks = $this->smsModel->getSmsClickStats($sms->getId());

        $translations = $sms->getTranslations();
        if ($translations instanceof Collection) {
            $translations = $translations->toArray();
        }

        [$translationParent, $translationChildren] = $translations;

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_sms_action', ['objectAction' => 'view', 'objectId' => $sms->getId()]),
            'viewParameters' => [
                'sms'         => $sms,
                'trackables'  => $trackableLinks,
                'logs'        => $logs,
                'isEmbedded'  => $request->get('isEmbedded') ?: false,
                'permissions' => $security->isGranted([
                    'sms:smses:viewown',
                    'sms:smses:viewother',
                    'sms:smses:create',
                    'sms:smses:editown',
                    'sms:smses:editother',
                    'sms:smses:deleteown',
                    'sms:smses:deleteother',
                    'sms:smses:publishown',
                    'sms:smses:publishother',
                ], 'RETURN_ARRAY'),
                'security'    => $security,
                'entityViews' => $entityViews,
                'contacts'    => $this->forward(
                    'Mautic\SmsBundle\Controller\SmsController::contactsAction',
                    [
                        'objectId'   => $sms->getId(),
                        'page'       => $request->getSession()->get('mautic.sms.contact.page', 1),
                        'ignoreAjax' => true,
                    ]
                )->getContent(),
                'dateRangeForm' => $dateRangeForm->createView(),
                'translations'  => [
                    'parent'   => $translationParent,
                    'children' => $translationChildren,
                ],
            ],
            'contentTemplate' => '@MauticSms/Sms/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param Sms $entity
     */
    public function newAction(Request $request, $entity = null): Response
    {
        if (!$entity instanceof Sms) {
            /** @var Sms $entity */
            $entity = $this->smsModel->getEntity();
        }

        $method  = $request->getMethod();
        $session = $request->getSession();

        if (!$this->security->isGranted('sms:smses:create')) {
            $this->throwAccessDenied();
        }

        // set the page we came from
        $page         = $session->get('mautic.sms.page', 1);
        $action       = $this->generateUrl('mautic_sms_action', ['objectAction' => 'new']);
        $sms          = $request->request->all()['sms'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($sms['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        if ($updateSelect) {
            $entity->setSmsType('template');
        }

        // create the form
        $form = $this->smsModel->createForm($entity, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if ('POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    if (!$entity->getIsMms()) {
                        $entity->setMedia([]);
                    }
                    // form is valid so process the data
                    $this->smsModel->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_sms_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_sms_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $returnUrl = $this->generateUrl('mautic_sms_action', $viewParameters);
                        $template  = 'Mautic\SmsBundle\Controller\SmsController::viewAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_sms_index', $viewParameters);
                $template       = 'Mautic\SmsBundle\Controller\SmsController::indexAction';
                // clear any modified content
                $session->remove('mautic.sms.'.$entity->getId().'.content');
            }

            $passthrough = [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
            ];

            // Check to see if this is a popup
            if (isset($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    [
                        'updateSelect' => $form['updateSelect']->getData(),
                        'id'           => $entity->getId(),
                        'name'         => $entity->getName(),
                        'group'        => $entity->getLanguage(),
                    ]
                );
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => $passthrough,
                    ]
                );
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                    'sms'  => $entity,
                ],
                'contentTemplate' => '@MauticSms/Sms/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_sms_action',
                        [
                            'objectAction' => 'new',
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @param bool $ignorePost
     * @param bool $forceTypeSelection
     */
    public function editAction(Request $request, $objectId, $ignorePost = false, $forceTypeSelection = false): JsonResponse|RedirectResponse|Response
    {
        $method  = $request->getMethod();
        $entity  = $this->smsModel->getEntity($objectId);
        $session = $request->getSession();
        $page    = $session->get('mautic.sms.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ];

        // not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.sms.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }
        if (!$this->security->hasEntityAccess(
            'sms:smses:viewown',
            'sms:smses:viewother',
            $entity->getCreatedBy()
        )
        ) {
            $this->throwAccessDenied();
        } elseif ($this->smsModel->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'sms');
        }

        // Create the form
        $action       = $this->generateUrl('mautic_sms_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $sms          = $request->request->all()['sms'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($sms['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        $form = $this->smsModel->createForm($entity, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    if (!$entity->getIsMms()) {
                        $entity->setMedia([]);
                    }
                    $this->smsModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_sms_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_sms_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ],
                        'warning'
                    );
                }
            } else {
                // clear any modified content
                $session->remove('mautic.sms.'.$objectId.'.content');
                // unlock the entity
                $this->smsModel->unlockEntity($entity);
            }

            $passthrough = [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
            ];

            $template = 'Mautic\SmsBundle\Controller\SmsController::viewAction';

            // Check to see if this is a popup
            if (isset($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    [
                        'updateSelect' => $form['updateSelect']->getData(),
                        'id'           => $entity->getId(),
                        'name'         => $entity->getName(),
                        'group'        => $entity->getLanguage(),
                    ]
                );
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $this->generateUrl('mautic_sms_action', $viewParameters),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                            'passthroughVars' => $passthrough,
                        ]
                    )
                );
            }
        } else {
            // lock the entity
            $this->smsModel->lockEntity($entity);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'               => $form->createView(),
                    'sms'                => $entity,
                    'forceTypeSelection' => $forceTypeSelection,
                ],
                'contentTemplate' => '@MauticSms/Sms/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_sms_action',
                        [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Clone an entity.
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        $entity = $this->smsModel->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('sms:smses:create')
                || !$this->security->hasEntityAccess(
                    'sms:smses:viewown',
                    'sms:smses:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                $this->throwAccessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($request, $entity);
    }

    /**
     * Deletes the entity.
     */
    public function deleteAction(Request $request, $objectId): Response
    {
        $page      = $request->getSession()->get('mautic.sms.page', 1);
        $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $entity = $this->smsModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.sms.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'sms:smses:deleteown',
                'sms:smses:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                $this->throwAccessDenied();
            } elseif ($this->smsModel->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'sms');
            }

            $this->smsModel->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getName(),
                    '%id%'   => $objectId,
                ],
            ];
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                ['flashes' => $flashes]
            )
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.sms.page', 1);
        $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $ids = json_decode($request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $this->smsModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.sms.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'sms:smses:viewown',
                    'sms:smses:viewother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->smsModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'sms', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if ([] !== $deleteIds) {
                $entities = $this->smsModel->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.sms.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                ['flashes' => $flashes]
            )
        );
    }

    public function previewAction($objectId): Response
    {
        $sms      = $this->smsModel->getEntity($objectId);
        $security = $this->security;

        if (null !== $sms && $security->hasEntityAccess('sms:smses:viewown', 'sms:smses:viewother')) {
            return $this->delegateView([
                'viewParameters' => [
                    'sms' => $sms,
                ],
                'contentTemplate' => '@MauticSms/Sms/preview.html.twig',
            ]);
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * @param int $page
     */
    #[Route('/s/sms/view/{objectId}/contact/{page}', name: 'mautic_sms_contacts', requirements: ['page' => '\d+', 'objectId' => '[a-zA-Z0-9_-]+'], defaults: ['page' => 0, 'objectId' => 0])]
    public function contactsAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        $objectId,
        $page = 1,
    ): Response {
        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            'sms:smses:view',
            'sms',
            'sms_message_stats',
            'sms',
            'sms_id'
        );
    }

    protected function getModelName(): string
    {
        return 'sms';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
