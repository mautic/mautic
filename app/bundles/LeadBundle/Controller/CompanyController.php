<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Form\Type\CompanyMergeType;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends FormController
{
    use LeadDetailsTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                'lead:leads:create',
                'lead:leads:editother',
                'lead:leads:editown',
                'lead:leads:deleteown',
                'lead:leads:deleteother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['lead:leads:viewother'] && !$permissions['lead:leads:viewown']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.company', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.company.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.company.orderby', 'comp.companyname');
        $orderByDir = $request->getSession()->get('mautic.company.orderbydir', 'ASC');

        $companies = $this->getModel('lead.company')->getEntities(
            [
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'withTotalCount' => true,
            ]
        );

        $request->getSession()->set('mautic.company.filter', $search);

        $count     = $companies['count'];
        $companies = $companies['results'];

        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'company',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        $tmpl  = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';
        $model = $this->getModel('lead.company');
        \assert($model instanceof CompanyModel);
        $companyIds = array_keys($companies);
        $leadCounts = (!empty($companyIds)) ? $model->getRepository()->getLeadCount($companyIds) : [];

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'leadCounts'  => $leadCounts,
                    'items'       => $companies,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'        => $tmpl,
                    'totalItems'  => $count,
                ],
                'contentTemplate' => '@MauticLead/Company/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'route'         => $this->generateUrl('mautic_company_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Refresh contacts list in company view with new parameters like order or page.
     *
     * @param int $objectId company id
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsListAction(Request $request, $objectId, $page = 1)
    {
        if (empty($objectId)) {
            return $this->accessDenied();
        }

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                'lead:leads:create',
                'lead:leads:editown',
                'lead:leads:editother',
                'lead:leads:deleteown',
                'lead:leads:deleteother',
            ],
            'RETURN_ARRAY'
        );

        /** @var CompanyModel $model */
        $model  = $this->getModel('lead.company');

        /** @var \Mautic\LeadBundle\Entity\Company $company */
        $company = $model->getEntity($objectId);

        $companiesRepo  = $model->getCompanyLeadRepository();
        $contacts       = $companiesRepo->getCompanyLeads($objectId);

        $leadIds = array_column($contacts, 'lead_id');

        $data = $this->getCompanyContacts($request, $objectId, $page, $leadIds);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'company'     => $company,
                    'page'        => $data['page'],
                    'contacts'    => $data['items'],
                    'totalItems'  => $data['count'],
                    'limit'       => $data['limit'],
                    'permissions' => $permissions,
                    'security'    => $this->security,
                ],
                'contentTemplate' => '@MauticLead/Company/list_rows_contacts.html.twig',
            ]
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \Mautic\LeadBundle\Entity\Company $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, $entity = null)
    {
        $model = $this->getModel('lead.company');
        \assert($model instanceof CompanyModel);

        if (!($entity instanceof Company)) {
            /** @var \Mautic\LeadBundle\Entity\Company $entity */
            $entity = $model->getEntity();
        }

        if (!$this->security->isGranted('lead:leads:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page         = $request->getSession()->get('mautic.company.page', 1);
        $method       = $request->getMethod();
        $action       = $this->generateUrl('mautic_company_action', ['objectAction' => 'new']);
        $company      = $request->request->get('company') ?? [];
        $updateSelect = InputHelper::clean(
            'POST' === $method
                ? ($company['updateSelect'] ?? false)
                : $request->get('updateSelect', false)
        );

        $leadFieldModel = $this->getModel('lead.field');
        \assert($leadFieldModel instanceof FieldModel);
        $fields = $leadFieldModel->getPublishedFieldArrays('company');
        $form   = $model->createForm($entity, $this->formFactory, $action, ['fields' => $fields, 'update_select' => $updateSelect]);

        $viewParameters = ['page' => $page];
        $returnUrl      = $this->generateUrl('mautic_company_index', $viewParameters);
        $template       = 'Mautic\LeadBundle\Controller\CompanyController::indexAction';

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    // get custom field values
                    $data = $request->request->get('company');
                    // pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }
                    $model->setFieldValues($entity, $data, true);
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_company_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_company_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                        $template  = 'Mautic\LeadBundle\Controller\CompanyController::indexAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $entity->getId(), true);
                    }
                }
            }

            $passthrough = [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ];

            // Check to see if this is a popup
            if (!empty($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    [
                        'updateSelect' => $form['updateSelect']->getData(),
                        'id'           => $entity->getId(),
                        'name'         => $entity->getName(),
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

        $fields = $model->organizeFieldsByGroup($fields);
        $groups = array_keys($fields);
        sort($groups);
        $template = '@MauticLead/Company/form_'.($request->get('modal', false) ? 'embedded' : 'standalone').'.html.twig';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'fields' => $fields,
                    'groups' => $groups,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'updateSelect'  => ('POST' === $request->getMethod()) ? $updateSelect : null,
                    'route'         => $this->generateUrl(
                        'mautic_company_action',
                        [
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), // valid means a new form was applied
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        $model = $this->getModel('lead.company');
        \assert($model instanceof CompanyModel);
        $entity = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.company.page', 1);

        $viewParameters = ['page' => $page];

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        // form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.company.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->security->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $entity->getOwner())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'lead.company');
        }

        $action       = $this->generateUrl('mautic_company_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $method       = $request->getMethod();
        $company      = $request->request->get('company') ?? [];
        $updateSelect = 'POST' === $method
            ? ($company['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        $leadFieldModel = $this->getModel('lead.field');
        \assert($leadFieldModel instanceof FieldModel);
        $fields = $leadFieldModel->getPublishedFieldArrays('company');
        $form   = $model->createForm(
            $entity,
            $this->formFactory,
            $action,
            ['fields' => $fields, 'update_select' => $updateSelect]
        );

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data = $request->request->get('company');
                    // pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }

                    $model->setFieldValues($entity, $data, true);

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_company_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_company_action',
                                [
                                    'objectAction' => 'view',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                        $template  = 'Mautic\LeadBundle\Controller\CompanyController::indexAction';
                    }
                }
            } else {
                // unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                $template  = 'Mautic\LeadBundle\Controller\CompanyController::indexAction';
            }

            $passthrough = [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ];

            // Check to see if this is a popup
            if (!empty($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    [
                        'updateSelect' => $form['updateSelect']->getData(),
                        'id'           => $entity->getId(),
                        'name'         => $entity->getName(),
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
            } elseif ($valid) {
                // Refetch and recreate the form in order to populate data manipulated in the entity itself
                $company = $model->getEntity($objectId);
                $form    = $model->createForm($company, $this->formFactory, $action, ['fields' => $fields, 'update_select' => $updateSelect]);
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        $fields = $model->organizeFieldsByGroup($fields);
        $groups = array_keys($fields);
        sort($groups);
        $template = '@MauticLead/Company/form_'.($request->get('modal', false) ? 'embedded' : 'standalone').'.html.twig';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'fields' => $fields,
                    'groups' => $groups,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_company_action',
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
     * Loads a specific company into the detailed panel.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Request $request, $objectId)
    {
        /** @var CompanyModel $model */
        $model  = $this->getModel('lead.company');

        $company = $model->getEntity($objectId);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        if (null === $company) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.company.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }

        /** @var \Mautic\LeadBundle\Entity\Company $company */
        $model->getRepository()->refetchEntity($company);

        // set some permissions
        $permissions = $this->security->isGranted(
            [
              'lead:leads:viewown',
              'lead:leads:viewother',
              'lead:leads:create',
              'lead:leads:editown',
              'lead:leads:editother',
              'lead:leads:deleteown',
              'lead:leads:deleteother',
            ],
            'RETURN_ARRAY'
        );

        if (!$this->security->hasEntityAccess(
            'lead:leads:viewown',
            'lead:leads:viewother',
            $company->getPermissionUser()
        )
        ) {
            return $this->accessDenied();
        }

        $fields         = $company->getFields();
        $companiesRepo  = $model->getCompanyLeadRepository();
        $contacts       = $companiesRepo->getCompanyLeads($objectId);

        $leadIds = array_column($contacts, 'lead_id');

        $engagementData = is_array($contacts) ? $this->getCompanyEngagementsForGraph($contacts) : [];

        $contacts = $this->getCompanyContacts($request, $objectId, 0, $leadIds);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'company'           => $company,
                    'fields'            => $fields,
                    'items'             => $contacts['items'],
                    'permissions'       => $permissions,
                    'engagementData'    => $engagementData,
                    'security'          => $this->security,
                    'page'              => $contacts['page'],
                    'totalItems'        => $contacts['count'],
                    'limit'             => $contacts['limit'],
                ],
                'contentTemplate' => '@MauticLead/Company/company.html.twig',
            ]
        );
    }

    /**
     * Get company's contacts for company view.
     *
     * @param int        $companyId
     * @param int        $page
     * @param array<int> $leadIds   filter to get only company's contacts
     */
    public function getCompanyContacts(Request $request, $companyId, $page = 0, $leadIds = []): array
    {
        $this->setListFilters();

        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model   = $this->getModel('lead');
        $session = $request->getSession();
        // set limits
        $limit = $session->get('mautic.company.'.$companyId.'.contacts.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        // do some default sorting
        $orderBy    = $session->get('mautic.company.'.$companyId.'.contacts.orderby', 'l.last_active');
        $orderByDir = $session->get('mautic.company.'.$companyId.'.contacts.orderbydir', 'DESC');

        // filter by company contacts
        $filter = [
          'force' => [
            ['column' => 'l.id', 'expr' => 'in', 'value' => $leadIds],
          ],
        ];

        $results = $model->getEntities([
            'start'          => $start,
            'limit'          => $limit,
            'filter'         => $filter,
            'orderBy'        => $orderBy,
            'orderByDir'     => $orderByDir,
            'withTotalCount' => true,
        ]);

        $count = $results['count'];
        unset($results['count']);

        $leads = $results['results'];
        unset($results);

        return [
            'items' => $leads,
            'page'  => $page,
            'count' => $count,
            'limit' => $limit,
        ];
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, $objectId)
    {
        $model  = $this->getModel('lead.company');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('lead:leads:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($request, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.company.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('lead.company');
            \assert($model instanceof CompanyModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.company.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted('lead:leads:deleteother')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'lead.company');
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
     * Deletes a group of entities.
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.company.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('lead.company');
            \assert($model instanceof CompanyModel);
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.company.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted('lead:leads:deleteother')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead.company', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);
                $deleted  = count($entities);
                $this->addFlashMessage(
                    'mautic.company.notice.batch_deleted',
                    [
                        '%count%'     => $deleted,
                    ]
                );
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
     * Company Merge function.
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function mergeAction(Request $request, $objectId)
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewother',
                'lead:leads:create',
                'lead:leads:editother',
                'lead:leads:deleteother',
            ],
            'RETURN_ARRAY'
        );
        /** @var CompanyModel $model */
        $model            = $this->getModel('lead.company');
        $secondaryCompany = $model->getEntity($objectId);
        $page             = $request->getSession()->get('mautic.lead.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        if (null === $secondaryCompany) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.company.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }

        $action = $this->generateUrl('mautic_company_action', ['objectAction' => 'merge', 'objectId' => $secondaryCompany->getId()]);

        $form = $this->formFactory->create(
            CompanyMergeType::class,
            [],
            [
                'action'      => $action,
                'main_entity' => $secondaryCompany->getId(),
            ]
        );

        if ('POST' === $request->getMethod()) {
            $valid = true;
            if (!$this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data           = $form->getData();
                    $primaryMergeId = $data['company_to_merge'];
                    $primaryCompany = $model->getEntity($primaryMergeId);

                    if (null === $primaryCompany) {
                        return $this->postActionRedirect(
                            array_merge(
                                $postActionVars,
                                [
                                    'flashes' => [
                                        [
                                            'type'    => 'error',
                                            'msg'     => 'mautic.company.error.notfound',
                                            'msgVars' => ['%id%' => $primaryCompany->getId()],
                                        ],
                                    ],
                                ]
                            )
                        );
                    } elseif (!$permissions['lead:leads:editother']) {
                        return $this->accessDenied();
                    } elseif ($model->isLocked($secondaryCompany)) {
                        // deny access if the entity is locked
                        return $this->isLocked($postActionVars, $primaryCompany, 'lead.company');
                    } elseif ($model->isLocked($primaryCompany)) {
                        // deny access if the entity is locked
                        return $this->isLocked($postActionVars, $primaryCompany, 'lead.company');
                    }

                    // Both leads are good so now we merge them
                    $model->companyMerge($primaryCompany, $secondaryCompany);
                }

                if ($valid) {
                    $viewParameters = [
                        'objectId'     => $primaryCompany->getId(),
                        'objectAction' => 'view',
                    ];
                }
            } else {
                $viewParameters = [
                    'objectId'     => $secondaryCompany->getId(),
                    'objectAction' => 'view',
                ];
            }

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $this->generateUrl('mautic_company_action', $viewParameters),
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'Mautic\LeadBundle\Controller\CompanyController::viewAction',
                    'passthroughVars' => [
                        'closeModal' => 1,
                    ],
                ]
            );
        }

        $tmpl = $request->get('tmpl', 'index');

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'         => $tmpl,
                    'action'       => $action,
                    'form'         => $form->createView(),
                    'currentRoute' => $this->generateUrl(
                        'mautic_company_action',
                        [
                            'objectAction' => 'merge',
                            'objectId'     => $secondaryCompany->getId(),
                        ]
                    ),
                ],
                'contentTemplate' => '@MauticLead/Company/merge.html.twig',
                'passthroughVars' => [
                    'route'  => false,
                    'target' => ('update' == $tmpl) ? '.company-merge-options' : null,
                ],
            ]
        );
    }

    /**
     * Export company's data.
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function companyExportAction(Request $request, ExportHelper $exportHelper, $companyId)
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        /** @var companyModel $companyModel */
        $companyModel  = $this->getModel('lead.company');
        $company       = $companyModel->getEntity($companyId);
        $dataType      = $request->get('filetype', 'csv');

        if (empty($company)) {
            return $this->notFound();
        }

        $companyFields = $company->getProfileFields();
        $export        = [];
        foreach ($companyFields as $alias=>$companyField) {
            $export[] = [
                'alias' => $alias,
                'value' => $companyField,
            ];
        }

        return $this->exportResultsAs($export, $dataType, 'company_data_'.($companyFields['companyemail'] ?: $companyFields['id']), $exportHelper);
    }
}
