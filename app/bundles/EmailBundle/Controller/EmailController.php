<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Controller\BuilderControllerTrait;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Controller\FormErrorMessagesTrait;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\BuilderSectionType;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Form\Type\BatchSendType;
use Mautic\EmailBundle\Form\Type\ExampleSendType;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailController extends FormController
{
    use BuilderControllerTrait;
    use FormErrorMessagesTrait;
    use EntityContactsTrait;

    public const EXAMPLE_EMAIL_SUBJECT_PREFIX = '[TEST]';

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $page = 1)
    {
        $model = $this->getModel('email');

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'email:emails:viewown',
                'email:emails:viewother',
                'email:emails:create',
                'email:emails:editown',
                'email:emails:editother',
                'email:emails:deleteown',
                'email:emails:deleteother',
                'email:emails:publishown',
                'email:emails:publishother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['email:emails:viewown'] && !$permissions['email:emails:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();

        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.email.filter.placeholder'),
                'multiple'    => true,
            ],
        ];

        // Reset available groups
        $listFilters['filters']['groups'] = [];

        // set limits
        $limit = $session->get('mautic.email.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.email.filter', ''));
        $session->set('mautic.email.filter', $search);

        $filter = [
            'string' => $search,
            'force'  => [
                ['column' => 'e.variantParent', 'expr' => 'isNull'],
                ['column' => 'e.translationParent', 'expr' => 'isNull'],
            ],
        ];
        if (!$permissions['email:emails:viewother']) {
            $filter['force'][] =
                ['column' => 'e.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        // retrieve a list of Lead Lists
        $leadListModel = $this->getModel('lead.list');
        \assert($leadListModel instanceof ListModel);
        $listFilters['filters']['groups']['mautic.core.filter.lists'] = [
            'options' => $leadListModel->getUserLists(),
            'prefix'  => 'list',
        ];

        // retrieve a list of themes
        $listFilters['filters']['groups']['mautic.core.filter.themes'] = [
            'options' => $this->factory->getInstalledThemes('email'),
            'prefix'  => 'theme',
        ];

        $currentFilters = $session->get('mautic.email.list_filters', []);
        $updatedFilters = $request->get('filters', false);
        $ignoreListJoin = true;

        if ($updatedFilters) {
            // Filters have been updated

            // Parse the selected values
            $newFilters     = [];
            $updatedFilters = json_decode($updatedFilters, true);

            if ($updatedFilters) {
                foreach ($updatedFilters as $updatedFilter) {
                    [$clmn, $fltr] = explode(':', $updatedFilter);

                    $newFilters[$clmn][] = $fltr;
                }

                $currentFilters = $newFilters;
            } else {
                $currentFilters = [];
            }
        }
        $session->set('mautic.email.list_filters', $currentFilters);

        if (!empty($currentFilters)) {
            $listIds = $catIds = $templates = [];
            foreach ($currentFilters as $type => $typeFilters) {
                switch ($type) {
                    case 'list':
                        $key = 'lists';
                        break;
                    case 'category':
                        $key = 'categories';
                        break;
                    case 'theme':
                        $key = 'themes';
                        break;
                }

                $listFilters['filters']['groups']['mautic.core.filter.'.$key]['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    switch ($type) {
                        case 'list':
                            $listIds[] = (int) $fltr;
                            break;
                        case 'category':
                            $catIds[] = (int) $fltr;
                            break;
                        case 'theme':
                            $templates[] = $fltr;
                            break;
                    }
                }
            }

            if (!empty($listIds)) {
                $filter['force'][] = ['column' => 'l.id', 'expr' => 'in', 'value' => $listIds];
                $ignoreListJoin    = false;
            }

            if (!empty($catIds)) {
                $filter['force'][] = ['column' => 'c.id', 'expr' => 'in', 'value' => $catIds];
            }

            if (!empty($templates)) {
                $filter['force'][] = ['column' => 'e.template', 'expr' => 'in', 'value' => $templates];
            }
        }

        $orderBy    = $session->get('mautic.email.orderby', 'e.dateModified');
        $orderByDir = $session->get('mautic.email.orderbydir', $this->getDefaultOrderDirection());

        $emails = $model->getEntities(
            [
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'ignoreListJoin' => $ignoreListJoin,
            ]
        );

        $count = count($emails);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($count / $limit)) ?: 1;
            }

            $session->set('mautic.email.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_email_index',
                        'mauticContent' => 'email',
                    ],
                ]
            );
        }
        $session->set('mautic.email.page', $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'filters'     => $listFilters,
                    'items'       => $emails,
                    'totalItems'  => $count,
                    'page'        => $page,
                    'limit'       => $limit,
                    'tmpl'        => $request->get('tmpl', 'index'),
                    'permissions' => $permissions,
                    'model'       => $model,
                ],
                'contentTemplate' => '@MauticEmail/Email/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'email',
                    'route'         => $this->generateUrl('mautic_email_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Request $request, $objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->getModel('email');
        $security = $this->security;

        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $model->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.email.page', 1);

        // Init the date range filter form
        $dateRangeValues = $request->get('daterange', []);
        $action          = $this->generateUrl('mautic_email_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);

        if (null === $email) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_email_index',
                        'mauticContent' => 'email',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.email.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->security->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $email->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        // get A/B test information
        [$parent, $children]     = $email->getVariants();
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
        $criteria      = $model->getBuilderComponents($email, 'abTestWinnerCriteria');
        if (!empty($lastCriteria) && empty($variantError)) {
            if (isset($criteria['criteria'][$lastCriteria])) {
                $testSettings = $criteria['criteria'][$lastCriteria];

                $args = [
                    'email'      => $email,
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

        // get related translations
        [$translationParent, $translationChildren] = $email->getTranslations();

        // Audit Log
        $auditLog = $this->getModel('core.auditlog');
        \assert($auditLog instanceof AuditLogModel);
        $logs = $auditLog->getLogForObject('email', $email->getId(), $email->getDateAdded());

        // Get click through stats
        $trackableLinks = $model->getEmailClickStats($email->getId());

        return $this->delegateView(
            [
                'returnUrl' => $this->generateUrl(
                    'mautic_email_action',
                    [
                        'objectAction' => 'view',
                        'objectId'     => $email->getId(),
                    ]
                ),
                'viewParameters' => [
                    'email'        => $email,
                    'trackables'   => $trackableLinks,
                    'logs'         => $logs,
                    'isEmbedded'   => $request->get('isEmbedded') ?: false,
                    'variants'     => [
                        'parent'     => $parent,
                        'children'   => $children,
                        'properties' => $properties,
                        'criteria'   => $criteria['criteria'],
                    ],
                    'translations' => [
                        'parent'   => $translationParent,
                        'children' => $translationChildren,
                    ],
                    'permissions' => $security->isGranted(
                        [
                            'email:emails:viewown',
                            'email:emails:viewother',
                            'email:emails:create',
                            'email:emails:editown',
                            'email:emails:editother',
                            'email:emails:deleteown',
                            'email:emails:deleteother',
                            'email:emails:publishown',
                            'email:emails:publishother',
                        ],
                        'RETURN_ARRAY'
                    ),
                    'abTestResults' => $abTestResults,
                    'security'      => $security,
                    'previewUrl'    => $this->generateUrl(
                        'mautic_email_preview',
                        ['objectId' => $email->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'contacts' => $this->forward(
                        'Mautic\EmailBundle\Controller\EmailController::contactsAction',
                        [
                            'objectId'   => $email->getId(),
                            'page'       => $request->getSession()->get('mautic.email.contact.page', 1),
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                    'dateRangeForm' => $dateRangeForm->createView(),
                ],
                'contentTemplate' => '@MauticEmail/Email/details.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'email',
                ],
            ]
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \Mautic\EmailBundle\Entity\Email $entity
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, $entity = null)
    {
        $model = $this->getModel('email');
        \assert($model instanceof EmailModel);

        if (!($entity instanceof Email)) {
            /** @var \Mautic\EmailBundle\Entity\Email $entity */
            $entity = $model->getEntity();
        }

        $method  = $request->getMethod();
        $session = $request->getSession();

        if (!$this->security->isGranted('email:emails:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page         = $session->get('mautic.email.page', 1);
        $action       = $this->generateUrl('mautic_email_action', ['objectAction' => 'new']);
        $emailForm    = $request->request->get('emailform') ?? [];
        $updateSelect = 'POST' === $method
            ? ($emailForm['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        if ($updateSelect) {
            // Force type to template
            $entity->setEmailType('template');
        }

        // create the form
        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if ('POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                $formData = $request->request->all()['emailform'] ?? [];
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();

                    $entity->setCustomHtml($content);

                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_email_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_email_action',
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
                        $returnUrl = $this->generateUrl('mautic_email_action', $viewParameters);
                        $template  = 'Mautic\EmailBundle\Controller\EmailController::viewAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_email_index', $viewParameters);
                $template       = 'Mautic\EmailBundle\Controller\EmailController::indexAction';
                // clear any modified content
                $session->remove('mautic.emailbuilder.'.$entity->getSessionId().'.content');
            }

            $passthrough = [
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email',
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

        $slotTypes   = $model->getBuilderComponents($entity, 'slotTypes');
        $sections    = $model->getBuilderComponents($entity, 'sections');
        $sectionForm = $this->formFactory->create(BuilderSectionType::class);
        $routeParams = [
            'objectAction' => 'new',
        ];
        if ($updateSelect) {
            $routeParams['updateSelect'] = $updateSelect;
            $routeParams['contentOnly']  = 1;
        }

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'page:preference_center:viewown',
                'page:preference_center:viewother',
            ],
            'RETURN_ARRAY'
        );

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'          => $form->createView(),
                    'isVariant'     => $entity->isVariant(true),
                    'email'         => $entity,
                    'slots'         => $this->buildSlotForms($slotTypes),
                    'sections'      => $this->buildSlotForms($sections),
                    'themes'        => $this->factory->getInstalledThemes('email', true),
                    'builderAssets' => trim(preg_replace('/\s+/', ' ', $this->getAssetsForBuilder($assetsHelper, $translator, $request, $routerHelper, $coreParametersHelper))), // strip new lines
                    'sectionForm'   => $sectionForm->createView(),
                    'updateSelect'  => $updateSelect,
                    'permissions'   => $permissions,
                ],
                'contentTemplate' => '@MauticEmail/Email/form.html.twig',
                'passthroughVars' => [
                    'activeLink'      => '#mautic_email_index',
                    'mauticContent'   => 'email',
                    'updateSelect'    => $updateSelect,
                    'route'           => $this->generateUrl('mautic_email_action', $routeParams),
                    'validationError' => $this->getFormErrorForBuilder($form),
                ],
            ]
        );
    }

    /**
     * @param bool $ignorePost
     * @param bool $forceTypeSelection
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(
            Request $request,
            AssetsHelper $assetsHelper,
            Translator $translator,
            RouterInterface $routerHelper,
            CoreParametersHelper $coreParametersHelper,
            $objectId,
            $ignorePost = false,
            $forceTypeSelection = false
    ) {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model  = $this->getModel('email');
        $method = $request->getMethod();

        $entity  = $model->getEntity($objectId);
        $session = $request->getSession();
        $page    = $request->getSession()->get('mautic.email.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email',
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
                                'msg'     => 'mautic.email.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->security->hasEntityAccess(
            'email:emails:editown',
            'email:emails:editother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'email');
        }

        // Create the form
        $action       = $this->generateUrl('mautic_email_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $emailform    = $request->request->get('emailform') ?? [];
        $updateSelect = 'POST' === $method
            ? ($emailform['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        if ($updateSelect) {
            // Force type to template
            $entity->setEmailType('template');
        }
        /** @var Form $form */
        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();
                    $entity->setCustomHtml($content);

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_email_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_email_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );
                }
            } else {
                // clear any modified content
                $session->remove('mautic.emailbuilder.'.$objectId.'.content');
                // unlock the entity
                $model->unlockEntity($entity);
            }

            $template    = 'Mautic\EmailBundle\Controller\EmailController::viewAction';
            $passthrough = [
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email',
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
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $this->generateUrl('mautic_email_action', $viewParameters),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                            'passthroughVars' => $passthrough,
                        ]
                    )
                );
            } elseif ($valid && $this->getFormButton($form, ['buttons', 'apply'])->isClicked()) {
                // Rebuild the form in the case apply is clicked so that DEC content is properly populated if all were removed
                $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);

            // clear any modified content
            $session->remove('mautic.emailbuilder.'.$objectId.'.content');

            // Set to view content
            $template = $entity->getTemplate();
            if (empty($template)) {
                $content = $entity->getCustomHtml();
                $form['customHtml']->setData($content);
            }
        }

        $assets     = $form['assetAttachments']->getData();
        $assetModel = $this->getModel('asset');
        \assert($assetModel instanceof AssetModel);
        $attachmentSize = $assetModel->getTotalFilesize($assets);

        $slotTypes   = $model->getBuilderComponents($entity, 'slotTypes');
        $sections    = $model->getBuilderComponents($entity, 'sections');
        $sectionForm = $this->formFactory->create(BuilderSectionType::class);
        $routeParams = [
            'objectAction' => 'edit',
            'objectId'     => $entity->getId(),
        ];
        if ($updateSelect) {
            $routeParams['updateSelect'] = $updateSelect;
            $routeParams['contentOnly']  = 1;
        }

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'page:preference_center:viewown',
                'page:preference_center:viewother',
            ],
            'RETURN_ARRAY'
        );

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'               => $form->createView(),
                    'isVariant'          => $entity->isVariant(true),
                    'slots'              => $this->buildSlotForms($slotTypes),
                    'sections'           => $this->buildSlotForms($sections),
                    'themes'             => $this->factory->getInstalledThemes('email', true),
                    'email'              => $entity,
                    'forceTypeSelection' => $forceTypeSelection,
                    'attachmentSize'     => $attachmentSize,
                    'builderAssets'      => trim(preg_replace('/\s+/', ' ', $this->getAssetsForBuilder($assetsHelper, $translator, $request, $routerHelper, $coreParametersHelper))), // strip new lines
                    'sectionForm'        => $sectionForm->createView(),
                    'permissions'        => $permissions,
                    'previewUrl'         => $this->generateUrl(
                        'mautic_email_preview',
                        ['objectId' => $entity->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ],
                'contentTemplate' => '@MauticEmail/Email/form.html.twig',
                'passthroughVars' => [
                    'activeLink'      => '#mautic_email_index',
                    'mauticContent'   => 'email',
                    'updateSelect'    => InputHelper::clean($request->query->get('updateSelect')),
                    'route'           => $this->generateUrl('mautic_email_action', $routeParams),
                    'validationError' => $this->getFormErrorForBuilder($form),
                ],
            ]
        );
    }

    /**
     * Clone an entity.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, $objectId)
    {
        $model = $this->getModel('email');
        /** @var Email $entity */
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('email:emails:create')
                || !$this->security->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity      = clone $entity;
            $session     = $request->getSession();
            $contentName = 'mautic.emailbuilder.'.$entity->getSessionId().'.content';

            $session->set($contentName, $entity->getCustomHtml());
        }

        return $this->newAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.email.page', 1);
        $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('email');
            \assert($model instanceof EmailModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.email.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'email:emails:deleteown',
                'email:emails:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'email');
            }

            $model->deleteEntity($entity);

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
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Activate the builder.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function builderAction(Request $request, SlotsHelper $slotsHelper, $objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');

        // permission check
        if (str_contains($objectId, 'new')) {
            $isNew = true;
            if (!$this->security->isGranted('email:emails:create')) {
                return $this->accessDenied();
            }
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            $isNew  = false;
            $entity = $model->getEntity($objectId);
            if (null == $entity
                || !$this->security->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }
        }

        $template = InputHelper::clean($request->query->get('template'));
        $slots    = $this->factory->getTheme($template)->getSlots('email');

        // merge any existing changes
        $newContent = $request->getSession()->get('mautic.emailbuilder.'.$objectId.'.content', []);
        $content    = $entity->getContent();

        if (is_array($newContent)) {
            $content = array_merge($content, $newContent);
            // Update the content for processSlots
            $entity->setContent($content);
        }

        // Replace short codes to emoji
        $content = array_map(fn ($text) => EmojiHelper::toEmoji($text, 'short'), $content);

        $this->processSlots($slotsHelper, $slots, $entity);

        $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

        return $this->render(
            $logicalName,
            [
                'isNew'    => $isNew,
                'slots'    => $slots,
                'content'  => $content,
                'email'    => $entity,
                'template' => $template,
                'basePath' => $request->getBasePath(),
            ]
        );
    }

    /**
     * Create an AB test.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function abtestAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, $objectId)
    {
        $model  = $this->getModel('email');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            $parent = $entity->getVariantParent();

            if ($parent || !$this->security->isGranted('email:emails:create')
                || !$this->security->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            // Note this since it's cleared on __clone()
            $emailType = $entity->getEmailType();

            $clone = clone $entity;

            $clone->setIsPublished(false);
            $clone->setEmailType($emailType);
            $clone->setVariantParent($entity);
        }

        return $this->newAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $clone);
    }

    /**
     * Make the variant the main.
     *
     * @return array|Response
     */
    public function winnerAction(Request $request, $objectId)
    {
        // todo - add confirmation to button click
        $page      = $request->getSession()->get('mautic.email', 1);
        $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'page',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('email');
            \assert($model instanceof EmailModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.email.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'email:emails:editown',
                'email:emails:editother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'email');
            }

            $model->convertVariant($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.email.notice.activated',
                'msgVars' => [
                    '%name%' => $entity->getName(),
                    '%id%'   => $objectId,
                ],
            ];

            $postActionVars['viewParameters'] = [
                'objectAction' => 'view',
                'objectId'     => $objectId,
            ];
            $postActionVars['returnUrl']       = $this->generateUrl('mautic_page_action', $postActionVars['viewParameters']);
            $postActionVars['contentTemplate'] = 'Mautic\EmailBundle\Controller\EmailController::viewAction';
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Manually sends emails.
     *
     * @return Response
     */
    public function sendAction(Request $request, $objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model   = $this->getModel('email');
        $entity  = $model->getEntity($objectId);
        $session = $request->getSession();
        $page    = $session->get('mautic.email.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email',
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
                                'msg'     => 'mautic.email.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }

        if (!$entity->isPublished()) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.email.error.send.unpublished',
                                'msgVars' => [
                                    '%id%'   => $objectId,
                                    '%name%' => $entity->getName(),
                                ],
                            ],
                        ],
                    ]
                )
            );
        }

        if ('template' == $entity->getEmailType()
            || !$this->security->hasEntityAccess(
                'email:emails:viewown',
                'email:emails:viewother',
                $entity->getCreatedBy()
            )
        ) {
            return $this->accessDenied();
        }

        // Check that the parent is getting sent
        if ($variantParent = $entity->getVariantParent()) {
            return $this->redirectToRoute('mautic_email_action', [
                'objectAction' => 'send',
                'objectId'     => $variantParent->getId(),
            ]);
        }

        if ($translationParent = $entity->getTranslationParent()) {
            return $this->redirectToRoute('mautic_email_action', [
                'objectAction' => 'send',
                'objectId'     => $translationParent->getId(),
            ]);
        }

        $action   = $this->generateUrl('mautic_email_action', ['objectAction' => 'send', 'objectId' => $objectId]);
        $pending  = $model->getPendingLeads($entity, null, true);
        $form     = $this->formFactory->create(BatchSendType::class, [], ['action' => $action]);
        $complete = $request->request->get('complete', false);

        if ('POST' == $request->getMethod() && ($complete || $this->isFormValid($form))) {
            if (!$complete) {
                $progress = [0, (int) $pending];
                $session->set('mautic.email.send.progress', $progress);

                $stats = ['sent' => 0, 'failed' => 0, 'failedRecipients' => []];
                $session->set('mautic.email.send.stats', $stats);

                $status     = 'inprogress';

                $session->set('mautic.email.send.active', false);
            } else {
                $stats      = $session->get('mautic.email.send.stats');
                $progress   = $session->get('mautic.email.send.progress');
                $status     = (!empty($stats['failed'])) ? 'with_errors' : 'success';
            }

            $batchlimit      = $this->coreParametersHelper->get('mailer_memory_msg_limit');
            $contentTemplate = '@MauticEmail/Send/progress.html.twig';
            $viewParameters  = [
                'progress'   => $progress,
                'stats'      => $stats,
                'status'     => $status,
                'email'      => $entity,
                'batchlimit' => $batchlimit,
            ];
        } else {
            // process and send
            $contentTemplate = '@MauticEmail/Send/form.html.twig';
            $viewParameters  = [
                'form'    => $form->createView(),
                'email'   => $entity,
                'pending' => $pending,
            ];
        }

        return $this->delegateView(
            [
                'viewParameters'  => $viewParameters,
                'contentTemplate' => $contentTemplate,
                'passthroughVars' => [
                    'mauticContent' => 'emailSend',
                    'route'         => $action,
                ],
            ]
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.email.page', 1);
        $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_email_index',
                'mauticContent' => 'email',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('email');
            \assert($model instanceof EmailModel);
            $ids = json_decode($request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.email.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'email', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.email.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Generating the modal box content for
     * the send multiple example email option.
     */
    public function sendExampleAction(Request $request, $objectId)
    {
        $model = $this->getModel('email');
        \assert($model instanceof EmailModel);
        /** @var Email $entity */
        $entity = $model->getEntity($objectId);

        // not found or not allowed
        if (null === $entity
            || (!$this->security->hasEntityAccess(
                'email:emails:viewown',
                'email:emails:viewother',
                $entity->getCreatedBy()
            ))
        ) {
            return $this->postActionRedirect(
                [
                    'passthroughVars' => [
                        'closeModal' => 1,
                        'route'      => false,
                    ],
                ]
            );
        }

        // Get the quick add form
        $action = $this->generateUrl('mautic_email_action', ['objectAction' => 'sendExample', 'objectId' => $objectId]);
        $user   = $this->user;

        // We have to add prefix to example emails
        $subject = sprintf('%s %s', static::EXAMPLE_EMAIL_SUBJECT_PREFIX, $entity->getSubject());
        $entity->setSubject($subject);

        $form = $this->createForm(ExampleSendType::class, ['emails' => ['list' => [$user->getEmail()]]], ['action' => $action]);
        /* @var \Mautic\EmailBundle\Model\EmailModel $model */

        if ('POST' == $request->getMethod()) {
            $isCancelled = $this->isFormCancelled($form);
            $isValid     = $this->isFormValid($form);
            if (!$isCancelled && $isValid) {
                $emails = $form['emails']->getData()['list'];

                // Prepare a fake lead
                /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
                $fieldModel = $this->getModel('lead.field');
                $fields     = $fieldModel->getFieldList(false, false);
                array_walk(
                    $fields,
                    function (&$field): void {
                        $field = "[$field]";
                    }
                );
                $fields['id'] = 0;

                $errors = [];
                foreach ($emails as $email) {
                    if (!empty($email)) {
                        $users = [
                            [
                                // Setting the id, firstname and lastname to null as this is a unknown user
                                'id'        => '',
                                'firstname' => '',
                                'lastname'  => '',
                                'email'     => $email,
                            ],
                        ];

                        // Send to current user
                        $error = $model->sendSampleEmailToUser($entity, $users, $fields, [], [], false);
                        if (count($error)) {
                            array_push($errors, $error[0]);
                        }
                    }
                }

                if (0 != count($errors)) {
                    $this->addFlashMessage(implode('; ', $errors));
                } else {
                    $this->addFlashMessage('mautic.email.notice.test_sent_multiple.success');
                }
            }

            if ($isValid || $isCancelled) {
                return $this->postActionRedirect(
                    [
                        'passthroughVars' => [
                            'closeModal' => 1,
                            'route'      => false,
                        ],
                    ]
                );
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                ],
                'contentTemplate' => '@MauticEmail/Email/recipients.html.twig',
            ]
        );
    }

    /**
     * PreProcess page slots for public view.
     *
     * @param array $slots
     * @param Email $entity
     */
    private function processSlots(SlotsHelper $slotsHelper, $slots, $entity): void
    {
        $content     = $entity->getContent();

        // Set the slots
        foreach ($slots as $slot => $slotConfig) {
            // support previous format where email slots are not defined with config array
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            $value = $content[$slot] ?? '';
            $slotsHelper->set($slot, "<div data-slot=\"text\" id=\"slot-{$slot}\">{$value}</div>");
        }

        // add builder toolbar
        $slotsHelper->start('builder'); ?>
        <input type="hidden" id="builder_entity_id" value="<?php echo $entity->getSessionId(); ?>"/>
        <?php
        $slotsHelper->stop();
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        $objectId,
        $page = 1
    ) {
        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            ['email:emails:viewown', 'email:emails:viewother'],
            'email',
            'email_stats',
            'email',
            'email_id'
        );
    }

    public function getModelName(): string
    {
        return 'email';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
