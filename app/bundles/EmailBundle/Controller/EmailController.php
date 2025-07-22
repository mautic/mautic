<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\AssetBundle\Model\AssetModel;
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
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Form\Type\BatchSendType;
use Mautic\EmailBundle\Form\Type\ExampleSendType;
use Mautic\EmailBundle\Helper\EmailConfig;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\LeadBundle\Helper\FakeContactHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailController extends FormController
{
    use FormErrorMessagesTrait;
    use EntityContactsTrait;

    public const EXAMPLE_EMAIL_SUBJECT_PREFIX = '[TEST]';

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, EmailModel $model, EmailConfig $emailConfig, ThemeHelper $themeHelper, $page = 1)
    {
        $isDraftEnabled = $emailConfig->isDraftEnabled();
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
            'options' => $themeHelper->getInstalledThemes('email'),
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
                    [$column, $filter] = explode(':', $updatedFilter);

                    $newFilters[$column][] = $filter;
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
                    'searchValue'    => $search,
                    'filters'        => $listFilters,
                    'items'          => $emails,
                    'totalItems'     => $count,
                    'page'           => $page,
                    'limit'          => $limit,
                    'tmpl'           => $request->get('tmpl', 'index'),
                    'permissions'    => $permissions,
                    'model'          => $model,
                    'isDraftEnabled' => $isDraftEnabled,
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
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, EmailModel $model, EmailConfig $emailConfig, $objectId)
    {
        $security = $this->security;

        /** @var Email $email */
        $email = $model->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.email.page', 1);

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
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
        $trackableLinks  = $model->getEmailClickStats($email->getId());
        $draftPreviewUrl = null;
        if ($emailConfig->isDraftEnabled() && $email->hasDraft()) {
            $draftPreviewUrl = $this->generateUrl(
                'mautic_email_preview',
                [
                    'objectId'   => $email->getId(),
                    'objectType' => 'draft',
                ]
            );
        }

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

        $plainTextHelper = new PlainTextHelper();
        $plainTextHelper->setHtml($email->getCustomHtml());
        $emailPreview = $plainTextHelper->getPreview();

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
                    'emailPreview' => $emailPreview,
                    'trackables'   => $trackableLinks,
                    'logs'         => $logs,
                    'isEmbedded'   => $request->get('isEmbedded') ?: false,
                    'variants'     => $variants,
                    'translations' => $translations,
                    'permissions'  => $security->isGranted(
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
                    'abTestResults'   => $abTestResults,
                    'security'        => $security,
                    'draftPreviewUrl' => $draftPreviewUrl,
                    'previewUrl'      => $this->generateUrl(
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
                    'dateRangeForm'       => $dateRangeForm->createView(),
                    'previewSettingsForm' => $this->createForm(
                        ContentPreviewSettingsType::class,
                        null,
                        [
                            'type'         => ContentPreviewSettingsType::TYPE_EMAIL,
                            'objectId'     => $email->getId(),
                            'variants'     => $variants,
                            'translations' => $translations,
                        ]
                    )->createView(),
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
     * @param Email $entity
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, EmailConfig $emailConfig, EmailModel $model, ThemeHelper $themeHelper, $entity = null)
    {
        if (!($entity instanceof Email)) {
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
        $emailForm    = $request->request->all()['emailform'] ?? [];
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
                        return $this->editAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $emailConfig, $model, $themeHelper, $entity->getId(), true);
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
                    'themes'        => $themeHelper->getInstalledThemes('email', true),
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
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(
        Request $request,
        AssetsHelper $assetsHelper,
        Translator $translator,
        RouterInterface $routerHelper,
        CoreParametersHelper $coreParametersHelper,
        EmailConfig $emailConfig,
        EmailModel $model,
        ThemeHelper $themeHelper,
        $objectId,
        $ignorePost = false,
        $forceTypeSelection = false,
    ) {
        $method  = $request->getMethod();
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
        $emailform    = $request->request->all()['emailform'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($emailform['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        if ($updateSelect) {
            // Force type to template
            $entity->setEmailType('template');
        }
        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                $existingEmail = clone $entity;
                $this->restoreNullifiedFieldsDuringClone($existingEmail, $entity);
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();
                    $entity->setCustomHtml($content);

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    if (true === $emailConfig->isDraftEnabled() && !empty($entity->getId())) {
                        $this->dispatcher->dispatch(new EmailEditSubmitEvent(
                            $existingEmail,
                            $entity,
                            $this->getFormButton($form, ['buttons', 'save'])->isClicked(),
                            $this->getFormButton($form, ['buttons', 'apply'])->isClicked(),
                            $form->get('buttons')->has('save_draft') && $this->getFormButton($form, ['buttons', 'save_draft'])->isClicked(),
                            $form->get('buttons')->has('apply_draft') && $this->getFormButton($form, ['buttons', 'apply_draft'])->isClicked(),
                            $form->get('buttons')->has('discard_draft') && $this->getFormButton($form, ['buttons', 'discard_draft'])->isClicked()
                        ), EmailEvents::ON_EMAIL_EDIT_SUBMIT);
                    }

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

            if ($cancelled
                || ($valid && ($this->getFormButton($form, ['buttons', 'save'])->isClicked()
                    || ($form->get('buttons')->has('save_draft') && $this->getFormButton($form, ['buttons', 'save_draft'])->isClicked())
                    || ($form->get('buttons')->has('apply_draft') && $this->getFormButton($form, ['buttons', 'apply_draft'])->isClicked())
                    || ($form->get('buttons')->has('discard_draft') && $this->getFormButton($form, ['buttons', 'discard_draft'])->isClicked())))
            ) {
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
        $draftPreviewUrl = '';
        if (true === $emailConfig->isDraftEnabled() && $entity->hasDraft()) {
            $draftPreviewUrl = $this->generateUrl(
                'mautic_email_preview',
                ['objectId'       => $entity->getId(),
                    'objectType'  => 'draft',
                ]
            );
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'               => $form->createView(),
                    'isVariant'          => $entity->isVariant(true),
                    'themes'             => $themeHelper->getInstalledThemes('email', true),
                    'email'              => $entity,
                    'forceTypeSelection' => $forceTypeSelection,
                    'attachmentSize'     => $attachmentSize,
                    'permissions'        => $permissions,
                    'draftPreviewUrl'    => $draftPreviewUrl,
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
    public function cloneAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, EmailModel $model, ThemeHelper $themeHelper, $objectId)
    {
        $emailEntity  = $model->getEntity($objectId);
        $entity       = null;
        if ($emailEntity) {
            $entity       = clone $emailEntity;
        }
        $method  = $request->getMethod();
        $session = $request->getSession();
        $page    = $session->get('mautic.email.page', 1);

        $returnUrl = $this->generateUrl('mautic_email_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::viewAction',
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
        } elseif (!$this->security->isGranted('email:emails:create')
            || !$this->security->hasEntityAccess(
                'email:emails:viewown',
                'email:emails:viewother',
                $emailEntity->getCreatedBy()
            )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'email');
        }

        // Create the form
        $action = $this->generateUrl('mautic_email_action', ['objectAction' => 'clone', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if ('POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
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
                        ],
                        'warning'
                    );
                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $returnUrl = $this->generateUrl('mautic_email_action', $viewParameters);
                        $template  = 'Mautic\EmailBundle\Controller\EmailController::viewAction';
                    } else {
                        return $this->redirectToRoute('mautic_email_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]);
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
                    'themes'        => $themeHelper->getInstalledThemes('email', true),
                    'permissions'   => $permissions,
                ],
                'contentTemplate' => '@MauticEmail/Email/form.html.twig',
                'passthroughVars' => [
                    'activeLink'      => '#mautic_email_index',
                    'mauticContent'   => 'email',
                    'route'           => $action,
                    'validationError' => $this->getFormErrorForBuilder($form),
                ],
            ]
        );
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
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function builderAction(Request $request, ThemeHelper $themeHelper, $objectId)
    {
        /** @var EmailModel $model */
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

        // merge any existing changes
        $newContent = $request->getSession()->get('mautic.emailbuilder.'.$objectId.'.content', []);
        $content    = $entity->getContent();

        if (is_array($newContent)) {
            $content = array_merge($content, $newContent);
            // Update the content for processSlots
            $entity->setContent($content);
        }

        $logicalName = $themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

        return $this->render(
            $logicalName,
            [
                'isNew'    => $isNew,
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
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function abtestAction(Request $request, AssetsHelper $assetsHelper, Translator $translator, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper, EmailConfig $emailConfig, EmailModel $model, ThemeHelper $themeHelper, $objectId)
    {
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

        return $this->newAction($request, $assetsHelper, $translator, $routerHelper, $coreParametersHelper, $emailConfig, $model, $themeHelper, $clone);
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
        /** @var EmailModel $model */
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

        $translationParent = $entity->getTranslationParent();

        if ($translationParent instanceof Email) {
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
     */
    public function batchDeleteAction(Request $request): Response
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
    public function sendExampleAction(Request $request, $objectId, CorePermissions $security, EmailModel $model, LeadModel $leadModel, FakeContactHelper $fakeLeadHelper): Response
    {
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

        if ('POST' === $request->getMethod()) {
            $isCancelled = $this->isFormCancelled($form);
            $isValid     = $this->isFormValid($form);
            if (!$isCancelled && $isValid) {
                $emails              = $form['emails']->getData()['list'];
                $previewForContactId = null;

                // Use this contact data to fill email body content
                if ($form->has('contact_id')) {
                    $previewForContactId = (int) $form->getData()['contact_id'];
                }

                if ($previewForContactId
                    && (!$security->isAdmin()
                        || !$security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $user->getId())
                    )
                ) {
                    // disallow displaying contact information
                    $previewForContactId = null;
                }

                if ($previewForContactId) {
                    // We have one from request parameter
                    $fields = $leadModel->getRepository()->getLead($previewForContactId);
                    $fields = $model->enrichedContactWithCompanies($fields);
                }

                if (!isset($fields)) {
                    // Prepare a fake  contact
                    $fields = $fakeLeadHelper->prepareFakeContactWithPrimaryCompany();
                }

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
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        $objectId,
        $page = 1,
    ) {
        $permissions = [
            'lead:leads:viewown',
            'lead:leads:viewother',
            'email:emails:viewown',
            'email:emails:viewother',
        ];

        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            $permissions,
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

    private function restoreNullifiedFieldsDuringClone(Email $clonedEmail, Email $cloningEmail): void
    {
        $clonedEmail->setTranslationParent($cloningEmail->getTranslationParent());
        foreach ($cloningEmail->getTranslationChildren() as $translationChild) {
            $clonedEmail->addTranslationChild($translationChild);
        }
        $clonedEmail->setVariantParent($cloningEmail->getVariantParent());
        foreach ($cloningEmail->getVariantChildren() as $variantChild) {
            $clonedEmail->addVariantChild($variantChild);
        }
        $clonedEmail->setSentCount($cloningEmail->getSentCount());
        $clonedEmail->setReadCount($cloningEmail->getReadCount());
        $clonedEmail->setRevision($cloningEmail->getRevision());
        $clonedEmail->setVariantSentCount($cloningEmail->getVariantSentCount());
        $clonedEmail->setVariantReadCount($cloningEmail->getVariantReadCount());
        $clonedEmail->setVariantStartDate($cloningEmail->getVariantStartDate());
        $clonedEmail->setEmailType($cloningEmail->getEmailType());
        $clonedEmail->setDraft($cloningEmail->getDraft());
    }
}
