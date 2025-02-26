<?php

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Controller\FormErrorMessagesTrait;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\ContentPreviewSettingsType;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PageController extends FormController
{
    use FormErrorMessagesTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        $pageModel = $this->getModel('page.page');
        \assert($pageModel instanceof PageModel);
        $model = $pageModel;

        // set some permissions
        $permissions = $this->security->isGranted([
            'page:pages:viewown',
            'page:pages:viewother',
            'page:pages:create',
            'page:pages:editown',
            'page:pages:editother',
            'page:pages:deleteown',
            'page:pages:deleteother',
            'page:pages:publishown',
            'page:pages:publishother',
            'page:preference_center:viewown',
            'page:preference_center:viewother',
        ], 'RETURN_ARRAY');

        if (!$permissions['page:pages:viewown'] && !$permissions['page:pages:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.page', $page);

        $limit  = $pageHelper->getLimit();
        $start  = $pageHelper->getStart();
        $search = $request->get('search', $request->getSession()->get('mautic.page.filter', ''));
        $filter = ['string' => $search, 'force' => []];

        $request->getSession()->set('mautic.page.filter', $search);

        if (!$permissions['page:pages:viewother']) {
            $filter['force'][] = ['column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        if (!$permissions['page:preference_center:viewown'] && !$permissions['page:preference_center:viewother']) {
            $filter['where'][] = [
                'expr' => 'orX',
                'val'  => [
                    ['column' => 'p.isPreferenceCenter', 'expr' => 'isNull'],
                    ['column' => 'p.isPreferenceCenter', 'expr' => 'eq', 'value' => 0],
                ],
            ];
        } elseif (!$permissions['page:preference_center:viewother']) {
            $filter['where'][] = [
                'expr' => 'orX',
                'val'  => [
                    [
                        'expr' => 'orX',
                        'val'  => [
                            ['column' => 'p.isPreferenceCenter', 'expr' => 'isNull'],
                            ['column' => 'p.isPreferenceCenter', 'expr' => 'eq', 'value' => 0],
                        ],
                    ],
                    [
                        'expr' => 'andX',
                        'val'  => [
                            ['column' => 'p.isPreferenceCenter', 'expr' => 'eq', 'value' => 1],
                            ['column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()],
                        ],
                    ],
                ],
            ];
        }

        $translator = $this->translator;

        // do not list variants in the main list
        $filter['force'][] = ['column' => 'p.variantParent', 'expr' => 'isNull'];

        $langSearchCommand = $translator->trans('mautic.core.searchcommand.lang');
        if (!str_contains($search, "{$langSearchCommand}:")) {
            $filter['force'][] = ['column' => 'p.translationParent', 'expr' => 'isNull'];
        }

        $orderBy    = $request->getSession()->get('mautic.page.orderby', 'p.dateModified');
        $orderByDir = $request->getSession()->get('mautic.page.orderbydir', $this->getDefaultOrderDirection());
        $pages      = $model->getEntities(
            [
                'start'           => $start,
                'limit'           => $limit,
                'filter'          => $filter,
                'orderBy'         => $orderBy,
                'orderByDir'      => $orderByDir,
                'submissionCount' => true,
            ]
        );

        $count = count($pages);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_page_index',
                    'mauticContent' => 'page',
                ],
            ]);
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $pages,
                'categories'  => $pageModel->getLookupResults('category', '', 0),
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'security'    => $this->security,
            ],
            'contentTemplate' => '@MauticPage/Page/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @param int $objectId
     *
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        /** @var PageModel $model */
        $model = $this->getModel('page.page');
        // set some permissions
        $security   = $this->security;
        $activePage = $model->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.page.page', 1);

        if (null === $activePage) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_page_index',
                    'mauticContent' => 'page',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.page.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$security->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $activePage->getCreatedBy()
        )
            || ($activePage->getIsPreferenceCenter()
            && !$security->hasEntityAccess(
                'page:preference_center:viewown', 'page:preference_center:viewother', $activePage->getCreatedBy()
            ))) {
            return $this->accessDenied();
        }

        // get A/B test information
        [$parent, $children]     = $activePage->getVariants();
        $properties              = [];
        $variantError            = false;
        $weight                  = 0;
        if (count($children)) {
            foreach ($children as $c) {
                $variantSettings = $c->getVariantSettings();

                if (is_array($variantSettings) && isset($variantSettings['winnerCriteria'])) {
                    if ($c->isPublished()) {
                        if (!isset($lastCriteria)) {
                            $lastCriteria = $variantSettings['winnerCriteria'];
                        }

                        // make sure all the variants are configured with the same criteria
                        if ($lastCriteria != $variantSettings['winnerCriteria']) {
                            $variantError = true;
                        }

                        $weight += $variantSettings['weight'];
                    }
                } else {
                    $variantSettings['winnerCriteria'] = '';
                    $variantSettings['weight']         = 0;
                }

                $properties[$c->getId()] = $variantSettings;
            }

            $properties[$parent->getId()]['weight']         = 100 - $weight;
            $properties[$parent->getId()]['winnerCriteria'] = '';
        }

        $abTestResults = [];
        $criteria      = $model->getBuilderComponents($activePage, 'abTestWinnerCriteria');
        if (!empty($lastCriteria) && empty($variantError)) {
            // there is a criteria to compare the pages against so let's shoot the page over to the criteria function to do its thing
            if (isset($criteria['criteria'][$lastCriteria])) {
                $testSettings = $criteria['criteria'][$lastCriteria];

                $args = [
                    'page'       => $activePage,
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties,
                ];

                $event = new DetermineWinnerEvent($args);
                $this->dispatcher->dispatch(
                    $event,
                    $testSettings['event']
                );

                $abTestResults = $event->getAbTestResults();
            }
        }

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action          = $this->generateUrl('mautic_page_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);

        // Audit Log
        $auditLogModel = $this->getModel('core.auditlog');
        \assert($auditLogModel instanceof AuditLogModel);
        $logs = $auditLogModel->getLogForObject('page', $activePage->getId(), $activePage->getDateAdded());

        $pageviews = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['page_id' => $activePage->getId(), 'flag' => 'total_and_unique']
        );

        // get related translations
        [$translationParent, $translationChildren] = $activePage->getTranslations();

        $variants = [
            'parent'             => $parent,
            'children'           => $children,
            'properties'         => $properties,
            'criteria'           => $criteria['criteria'],
        ];

        $translations = [
            'parent'   => $translationParent,
            'children' => $translationChildren,
        ];

        return $this->delegateView([
            'returnUrl' => $this->generateUrl('mautic_page_action', [
                'objectAction' => 'view',
                'objectId'     => $activePage->getId(), ]
            ),
            'viewParameters' => [
                'activePage'   => $activePage,
                'variants'     => $variants,
                'translations' => $translations,
                'permissions'  => $security->isGranted([
                    'page:pages:viewown',
                    'page:pages:viewother',
                    'page:pages:create',
                    'page:pages:editown',
                    'page:pages:editother',
                    'page:pages:deleteown',
                    'page:pages:deleteother',
                    'page:pages:publishown',
                    'page:pages:publishother',
                    'page:preference_center:viewown',
                    'page:preference_center:viewother',
                ], 'RETURN_ARRAY'),
                'stats' => [
                    'pageviews' => $pageviews,
                    'hits'      => [
                        'total'  => $activePage->getHits(),
                        'unique' => $activePage->getUniqueHits(),
                    ],
                ],
                'abTestResults' => $abTestResults,
                'security'      => $security,
                'pageUrl'       => $model->generateUrl($activePage, true),
                'previewUrl'    => $this->generateUrl('mautic_page_preview', ['id' => $objectId], UrlGeneratorInterface::ABSOLUTE_URL),
                'logs'          => $logs,
                'dateRangeForm' => $dateRangeForm->createView(), 'previewSettingsForm' => $this->createForm(
                    ContentPreviewSettingsType::class,
                    null,
                    [
                        'type'         => ContentPreviewSettingsType::TYPE_PAGE,
                        'objectId'     => $activePage->getId(),
                        'variants'     => $variants,
                        'translations' => $translations,
                    ]
                )->createView(),
            ],
            'contentTemplate' => '@MauticPage/Page/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param Page|null $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, ThemeHelper $themeHelper, $entity = null)
    {
        /** @var PageModel $model */
        $model = $this->getModel('page.page');

        if (!($entity instanceof Page)) {
            /** @var Page $entity */
            $entity = $model->getEntity();
        }

        $method  = $request->getMethod();
        $session = $request->getSession();
        if (!$this->security->isGranted('page:pages:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page   = $session->get('mautic.page.page', 1);
        $action = $this->generateUrl('mautic_page_action', ['objectAction' => 'new']);

        // create the form
        $form = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if ('POST' == $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();
                    $entity->setCustomHtml($content);
                    $entity->setDateModified(new \DateTime());

                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_page_index',
                        '%url%'       => $this->generateUrl('mautic_page_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $returnUrl = $this->generateUrl('mautic_page_action', $viewParameters);
                        $template  = 'Mautic\PageBundle\Controller\PageController::viewAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $themeHelper, $entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_page_index', $viewParameters);
                $template       = 'Mautic\PageBundle\Controller\PageController::indexAction';
                // clear any modified content
                $session->remove('mautic.pagebuilder.'.$entity->getSessionId().'.content');
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_page_index',
                        'mauticContent' => 'page',
                    ],
                ]);
            }
        }

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'page:preference_center:editown',
                'page:preference_center:editother',
            ],
            'RETURN_ARRAY'
        );

        return $this->delegateView([
            'viewParameters' => [
                'form'          => $form->createView(),
                'isVariant'     => $entity->isVariant(true),
                'tokens'        => $model->getBuilderComponents($entity, 'tokens'),
                'activePage'    => $entity,
                'themes'        => $themeHelper->getInstalledThemes('page', true),
                'permissions'   => $permissions,
            ],
            'contentTemplate' => '@MauticPage/Page/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_action', [
                    'objectAction' => 'new',
                ]),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|Response
     */
    public function editAction(
        Request $request,
        AssetsHelper $assetsHelper,
        Translator $translator,
        RouterInterface $routerHelper,
        CoreParametersHelper $coreParametersHelper,
        ThemeHelper $themeHelper,
        $objectId,
        $ignorePost = false,
    ) {
        /** @var PageModel $model */
        $model    = $this->getModel('page.page');
        $security = $this->security;
        $entity   = $model->getEntity($objectId);
        $session  = $request->getSession();
        $page     = $request->getSession()->get('mautic.page.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        // not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.page.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif (!$security->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
        )
            || ($entity->getIsPreferenceCenter() && !$security->hasEntityAccess(
                'page:preference_center:viewown', 'page:preference_center:viewother', $entity->getCreatedBy()
            ))) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'page.page');
        }

        // Create the form
        $action = $this->generateUrl('mautic_page_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();
                    $entity->setCustomHtml($content);

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_page_index',
                        '%url%'       => $this->generateUrl('mautic_page_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            } else {
                // clear any modified content
                $session->remove('mautic.pagebuilder.'.$objectId.'.content');
                // unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'returnUrl'       => $this->generateUrl('mautic_page_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::viewAction',
                    ])
                );
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);

            // clear any modified content
            $session->remove('mautic.pagebuilder.'.$objectId.'.content');

            // set the lookup values
            $parent = $entity->getTranslationParent();
            if ($parent instanceof Page && isset($form['translationParent_lookup'])) {
                $form->get('translationParent_lookup')->setData($parent->getTitle());
            }

            // Set to view content
            $template = $entity->getTemplate();
            if (empty($template)) {
                $content = $entity->getCustomHtml();
                $form['customHtml']->setData($content);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'          => $form->createView(),
                'isVariant'     => $entity->isVariant(true),
                'tokens'        => $model->getBuilderComponents($entity, 'tokens'),
                'activePage'    => $entity,
                'themes'        => $themeHelper->getInstalledThemes('page', true),
                'previewUrl'    => $this->generateUrl('mautic_page_preview', ['id' => $objectId], UrlGeneratorInterface::ABSOLUTE_URL),
                'permissions'   => $security->isGranted(
                    [
                        'page:preference_center:editown',
                        'page:preference_center:editother',
                    ],
                    'RETURN_ARRAY'
                ),
                'security'      => $security,
            ],
            'contentTemplate' => '@MauticPage/Page/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                ]),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|Response
     */
    public function cloneAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, ThemeHelper $themeHelper, $objectId)
    {
        /** @var PageModel $model */
        $model  = $this->getModel('page.page');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('page:pages:create')
                || !$this->security->hasEntityAccess(
                    'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setHits(0);
            $entity->setUniqueHits(0);
            $entity->setRevision(0);
            $entity->setVariantStartDate(null);
            $entity->setVariantHits(0);
            $entity->setIsPublished(false);

            $session     = $request->getSession();
            $contentName = 'mautic.pagebuilder.'.$entity->getSessionId().'.content';

            $session->set($contentName, $entity->getCustomHtml());
        }

        return $this->newAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $themeHelper, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        if ('POST' == $request->getMethod()) {
            /** @var PageModel $model */
            $model  = $this->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.page.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'page:pages:deleteown',
                'page:pages:deleteother',
                $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId,
                ],
            ];
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
    public function batchDeleteAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        if ('POST' == $request->getMethod()) {
            /** @var PageModel $model */
            $model     = $this->getModel('page');
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.page.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'page:pages:deleteown', 'page:pages:deleteother', $entity->getCreatedBy()
                )) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'page', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.page.notice.batch_deleted',
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

    /**
     * Activate the builder.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function builderAction(Request $request, ThemeHelper $themeHelper, $objectId)
    {
        /** @var PageModel $model */
        $model = $this->getModel('page.page');

        // permission check
        if (str_contains((string) $objectId, 'new')) {
            $isNew = true;
            if (!$this->security->isGranted('page:pages:create')) {
                return $this->accessDenied();
            }
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            $isNew  = false;
            $entity = $model->getEntity($objectId);
            if (null == $entity || !$this->security->hasEntityAccess(
                'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            }
        }

        $template = InputHelper::clean($request->query->get('template'));
        if (empty($template)) {
            throw new \InvalidArgumentException('No template found');
        }

        $logicalName = $themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/page.html.twig');

        return $this->render($logicalName, [
            'isNew'       => $isNew,
            'formFactory' => $this->formFactory,
            'content'     => $entity->getContent(),
            'page'        => $entity,
            'template'    => $template,
            'basePath'    => $request->getBasePath(),
        ]);
    }

    /**
     * @param int $objectId
     *
     * @return JsonResponse|Response
     */
    public function abtestAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, ThemeHelper $themeHelper, $objectId)
    {
        /** @var PageModel $model */
        $model  = $this->getModel('page.page');
        $entity = $model->getEntity($objectId);

        if (!$entity) {
            return $this->notFound();
        }

        $parent = $entity->getVariantParent();

        if ($parent || !$this->security->isGranted('page:pages:create')
                || !$this->security->hasEntityAccess(
                    'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
                )
        ) {
            return $this->accessDenied();
        }

        $clone = clone $entity;

        // reset
        $clone->setHits(0);
        $clone->setRevision(0);
        $clone->setVariantHits(0);
        $clone->setUniqueHits(0);
        $clone->setVariantStartDate(null);
        $clone->setIsPublished(false);
        $clone->setVariantParent($entity);

        return $this->newAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $themeHelper, $clone);
    }

    /**
     * Make the variant the main.
     *
     * @return Response
     */
    public function winnerAction(Request $request, $objectId)
    {
        // todo - add confirmation to button click
        $page      = $request->getSession()->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        if ('POST' == $request->getMethod()) {
            /** @var PageModel $model */
            $model  = $this->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.page.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'page:pages:editown',
                'page:pages:editother',
                $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->convertVariant($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.page.notice.activated',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId,
                ],
            ];

            $postActionVars['viewParameters'] = [
                'objectAction' => 'view',
                'objectId'     => $objectId,
            ];
            $postActionVars['returnUrl']       = $this->generateUrl('mautic_page_action', $postActionVars['viewParameters']);
            $postActionVars['contentTemplate'] = 'Mautic\PageBundle\Controller\PageController::viewAction';
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Show submissions inside page.
     *
     * @param int $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function resultsAction(Request $request, $objectId, $page = 1)
    {
        /** @var PageModel $pageModel */
        $pageModel    = $this->getModel('page.page');
        $activePage   = $pageModel->getEntity($objectId);
        $session      = $request->getSession();
        $pageListPage = $session->get('mautic.page.page', 1);
        $returnUrl    = $this->generateUrl('mautic_page_index', ['page' => $pageListPage]);

        if (null === $activePage) {
            // redirect back to page list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $pageListPage],
                    'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_page_index',
                        'mauticContent' => 'page',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.page.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->security->hasEntityAccess(
            'page:pages:viewown',
            'page:pages:viewother',
            $activePage->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        if ('POST' == $request->getMethod()) {
            $this->setListFilters($request->query->get('name'));
        }

        // set limits
        $limit = $session->get('mautic.pageresult.'.$objectId.'.limit', $this->coreParametersHelper->get('default_pagelimit'));

        $page  = $page ?: 0;
        $start = ($page <= 1) ? 0 : (($page - 1) * $limit);

        // Set order direction to desc if not set
        if (!$session->get('mautic.pageresult.'.$objectId.'.orderbydir', null)) {
            $session->set('mautic.pageresult.'.$objectId.'.orderbydir', 'DESC');
        }

        $orderBy    = $session->get('mautic.pageresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.pageresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.pageresult.'.$objectId.'.filters', []);

        /** @var \Mautic\FormBundle\Model\SubmissionModel $model */
        $model = $this->getModel('form.submission');

        if ($request->query->has('result')) {
            // Force ID
            $filters['s.id'] = ['column' => 's.id', 'expr' => 'like', 'value' => (int) $request->query->get('result'), 'strict' => false];
            $session->set("mautic.pageresult.$objectId.filters", $filters);
        }
        // get the results
        $entities = $model->getEntitiesByPage(
            [
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => ['force' => $filters],
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'withTotalCount' => true,
                'simpleResults'  => true,
                'activePage'     => $activePage,
            ]
        );

        $count   = $entities['count'];
        $results = $entities['results'];
        unset($entities);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            $lastPage = (1 === $count) ? 1 : (((ceil($count / $limit)) ?: 1) ?: 1);
            $session->set('mautic.pageresult.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_page_results', ['objectId' => $objectId, 'page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::resultsAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_page_index',
                        'mauticContent' => 'pageresult',
                    ],
                ]
            );
        }

        // set what page currently on so that we can return here if need be
        $session->set('mautic.pageresult.page', $page);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'items'      => $results,
                    'filters'    => $filters,
                    'activePage' => $activePage,
                    'page'       => $page,
                    'totalCount' => $count,
                    'limit'      => $limit,
                    'tmpl'       => $tmpl,
                ],
                'contentTemplate' => '@MauticPage/Result/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => 'mautic_page_index',
                    'mauticContent' => 'pageresult',
                    'route'         => $this->generateUrl(
                        'mautic_page_results',
                        [
                            'objectId' => $objectId,
                            'page'     => $page,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Export submissions from a page.
     *
     * @param int    $objectId
     * @param string $format
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|Response
     *
     * @throws \Exception
     */
    public function exportAction(Request $request, $objectId, $format = 'csv')
    {
        $pageModel    = $this->getModel('page.page');
        $activePage   = $pageModel->getEntity($objectId);
        $session      = $request->getSession();
        $pageListPage = $session->get('mautic.page.page', 1);
        $returnUrl    = $this->generateUrl('mautic_page_index', ['page' => $pageListPage]);

        if (null === $activePage) {
            // redirect back to page list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $pageListPage],
                    'contentTemplate' => 'Mautic\PageBundle\Controller\PageController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_page_index',
                        'mauticContent' => 'page',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.page.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->security->hasEntityAccess(
            'page:pages:viewown',
            'page:pages:viewother',
            $activePage->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        $orderBy    = $session->get('mautic.pageresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.pageresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.pageresult.'.$objectId.'.filters', []);

        $args = [
            'limit'      => false,
            'filter'     => ['force' => $filters],
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
            'activePage' => $activePage,
        ];

        /** @var \Mautic\FormBundle\Model\SubmissionModel $model */
        $model = $this->getModel('form.submission');

        return $model->exportResultsForPage($format, $activePage, $args);
    }

    public function getModelName(): string
    {
        return 'page';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
