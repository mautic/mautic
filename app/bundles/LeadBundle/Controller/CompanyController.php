<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\FindReplaceType;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\CustomFieldEntityInterface;
use Mautic\LeadBundle\Field\CustomFieldFindReplace;
use Mautic\LeadBundle\Field\DTO\CustomFieldFindReplaceCriteria;
use Mautic\LeadBundle\Form\Type\CompanyMergeType;
use Mautic\LeadBundle\Form\Type\OwnerType;
use Mautic\LeadBundle\Helper\CompanySearchScopeProvider;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Services\CompanyColumnsDictionary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class CompanyController extends FormController
{
    use LeadDetailsTrait;

    private CompanyRepository $companyRepository;

    private \Mautic\UserBundle\Entity\UserRepository $userRepository;

    private FieldModel $fieldModel;

    private CompanyModel $companyModel;

    private LeadModel $leadModel;

    #[Required]
    public function autowireCompanyController(
        LeadModel $leadModel,
        CompanyModel $companyModel,
        FieldModel $fieldModel,
        CompanyRepository $companyRepository,
        \Mautic\UserBundle\Entity\UserRepository $userRepository,
    ): void {
        $this->leadModel = $leadModel;
        $this->companyModel = $companyModel;
        $this->fieldModel = $fieldModel;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
    }

    public function batchOwnersAction(Request $request): JsonResponse|Response
    {
        if (!$this->security->isGranted('user:users:view')) {
            $this->throwAccessDenied();
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all()['lead_batch_owner'] ?? [];
            $ids  = json_decode($data['ids'] ?? '', true);
            $companies = is_array($ids) ? $this->companyModel->getEntities([
                'filter' => ['force' => [['column' => 'comp.id', 'expr' => 'in', 'value' => $ids]]],
                'ignore_paginator' => true,
            ]) : [];
            $count = 0;
            foreach ($companies as $company) {
                if ($this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $company->getPermissionUser())) {
                    $company->setOwner($this->userRepository->find((int) ($data['addowner'] ?? 0)));
                    ++$count;
                }
            }
            $this->companyModel->saveEntities($companies);
            $this->addFlashMessage('mautic.company.batch_companies_affected', ['%count%' => $count]);

            return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
        }

        $users = $this->userRepository->getUserList('', 0);
        $items = [];
        foreach ($users as $user) {
            $items[$user['firstName'].' '.$user['lastName'].' ('.$user['id'].')'] = $user['id'];
        }
        $route = $this->generateUrl('mautic_company_action', ['objectAction' => 'batchOwners']);

        return $this->delegateView([
            'viewParameters' => ['form' => $this->createForm(OwnerType::class, [], ['items' => $items, 'action' => $route])->createView()],
            'contentTemplate' => '@MauticLead/Batch/form.html.twig',
            'passthroughVars' => ['activeLink' => '#mautic_company_index', 'mauticContent' => 'companyBatch', 'route' => $route],
        ]);
    }

    #[Route(
        '/s/companies/{objectAction}/{objectId}',
        name: 'mautic_company_action',
        requirements: ['objectId' => '[a-zA-Z0-9_-]+'],
        defaults: ['objectId' => 0],
    )]
    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = ''): Response
    {
        return parent::executeAction($request, $objectAction, $objectId, $objectSubId, $objectModel);
    }

    #[Route(
        '/s/companies/{page}',
        name: 'mautic_company_index',
        requirements: ['page' => '\d+'],
        defaults: ['page' => 0],
    )]
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, CompanyColumnsDictionary $companyColumnsDictionary, CompanySearchScopeProvider $companySearchScopeProvider, int $page = 1): Response
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
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.company', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.company.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.company.orderby', 'comp.companyname');
        $orderByDir = $request->getSession()->get('mautic.company.orderbydir', 'ASC');

        $companies = $this->companyModel->getEntities(
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
        $companyIds = array_keys($companies);
        $leadCounts = ([] !== $companyIds) ? $this->companyRepository->getLeadCount($companyIds) : [];

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'leadCounts'  => $leadCounts,
                    'columns'     => $companyColumnsDictionary->getColumns(),
                    'items'       => $companies,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'            => $tmpl,
                    'totalItems'      => $count,
                    'searchScopes'    => $companySearchScopeProvider->getScopes(),
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
     */
    #[Route(
        '/s/company/{objectId}/contacts/{page}',
        name: 'mautic_company_contacts_list',
        requirements: ['objectId' => '\d+', 'page' => '\d+'],
        defaults: ['page' => 0, 'objectId' => 0],
    )]
    public function contactsListAction(Request $request, $objectId, $page = 1): Response
    {
        if (empty($objectId)) {
            $this->throwAccessDenied();
        }

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

        $companiesRepo  = $this->companyModel->getCompanyLeadRepository();
        $contacts       = $companiesRepo->getCompanyLeads($objectId);

        $leadIds = array_column($contacts, 'lead_id');

        $data = $this->getCompanyContacts($request, $objectId, $page, $leadIds);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'company_id'  => $objectId,
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
     * @param Company $entity
     */
    public function newAction(Request $request, $entity = null): Response
    {
        if (!$entity instanceof Company) {
            /** @var Company $entity */
            $entity = $this->companyModel->getEntity();
        }

        if (!$this->security->isGranted('lead:leads:create')) {
            $this->throwAccessDenied();
        }

        // set the page we came from
        $page         = $request->getSession()->get('mautic.company.page', 1);
        $method       = $request->getMethod();
        $action       = $this->generateUrl('mautic_company_action', ['objectAction' => 'new']);
        $company      = $request->request->all()['company'] ?? [];
        $updateSelect = InputHelper::clean(
            'POST' === $method
                ? ($company['updateSelect'] ?? false)
                : $request->get('updateSelect', false)
        );
        $fields = $this->fieldModel->getPublishedFieldArrays('company');
        $form   = $this->companyModel->createForm($entity, $action, ['fields' => $fields, 'update_select' => $updateSelect]);

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
                    $data = $request->request->all()['company'] ?? [];
                    // pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }
                    $this->companyModel->setFieldValues($entity, $data, true);
                    // form is valid so process the data
                    $this->companyModel->saveEntity($entity);

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
                        $viewParameters = ['objectAction' => 'view', 'objectId' => $entity->getId()];
                        $returnUrl      = $this->generateUrl('mautic_company_action', $viewParameters);
                        $template       = 'Mautic\LeadBundle\Controller\CompanyController::viewAction';
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

        $fields = $this->companyModel->organizeFieldsByGroup($fields);
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
     * @param int $objectId
     */
    public function editAction(Request $request, $objectId, bool $ignorePost = false): Response
    {
        $entity = $this->companyModel->getEntity($objectId);

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
        }
        if (!$this->security->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $entity->getPermissionUser())) {
            $this->throwAccessDenied();
        } elseif ($this->companyModel->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'lead.company');
        }

        $action       = $this->generateUrl('mautic_company_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $method       = $request->getMethod();
        $company      = $request->request->all()['company'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($company['updateSelect'] ?? false)
            : $request->get('updateSelect', false);
        $fields = $this->fieldModel->getPublishedFieldArrays('company');
        $form   = $this->companyModel->createForm(
            $entity,
            $action,
            ['fields' => $fields, 'update_select' => $updateSelect]
        );

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data = $request->request->all()['company'] ?? [];
                    // pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }

                    $this->companyModel->setFieldValues($entity, $data, true);

                    // form is valid so process the data
                    $this->companyModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

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
                        $viewParameters = ['objectAction' => 'view', 'objectId' => $objectId];
                        $returnUrl      = $this->generateUrl('mautic_company_action', $viewParameters);
                        $template       = 'Mautic\LeadBundle\Controller\CompanyController::viewAction';
                    }
                }
            } else {
                // unlock the entity
                $this->companyModel->unlockEntity($entity);

                $viewParameters = ['objectAction' => 'view', 'objectId' => $objectId];
                $returnUrl      = $this->generateUrl('mautic_company_action', $viewParameters);
                $template       = 'Mautic\LeadBundle\Controller\CompanyController::viewAction';
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
            if ($valid) {
                // Refetch and recreate the form in order to populate data manipulated in the entity itself
                $company = $this->companyModel->getEntity($objectId);
                $form    = $this->companyModel->createForm($company, $action, ['fields' => $fields, 'update_select' => $updateSelect]);
            }
        } else {
            // lock the entity
            $this->companyModel->lockEntity($entity);
        }

        $fields = $this->companyModel->organizeFieldsByGroup($fields);
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
     */
    public function viewAction($objectId): Response
    {
        $company = $this->companyModel->getEntity($objectId);

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

        /** @var Company $company */
        $this->companyRepository->refetchEntity($company);

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
            $this->throwAccessDenied();
        }

        $fields = $company->getFields();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'company'           => $company,
                    'fields'            => $fields,
                    'permissions'       => $permissions,
                    'security'          => $this->security,
                ],
                'contentTemplate' => '@MauticLead/Company/company.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'route'         => $this->generateUrl(
                        'mautic_company_action',
                        [
                            'objectAction' => 'view',
                            'objectId'     => $objectId,
                        ]
                    ),
                ],
            ]
        );
    }

    #[Route(
        '/s/company/graph/{objectId}',
        name: 'mautic_company_graph',
        requirements: ['objectId' => '[a-zA-Z0-9_-]+'],
        defaults: ['objectId' => 0],
    )]
    public function graphAction(CompanyLeadRepository $companiesRepo, int $objectId): Response
    {
        $contacts       = $companiesRepo->getCompanyLeads($objectId);
        $engagementData = is_array($contacts) ? $this->getCompanyEngagementsForGraph($contacts) : [];

        return $this->ajaxAction(
            $this->requestStack->getCurrentRequest(),
            [
                'contentTemplate' => '@MauticCore/Helper/chart.html.twig',
                'viewParameters'  => [
                    'chartData'   => $engagementData,
                    'chartType'   => 'line',
                    'chartHeight' => 250,
                ],
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

        $results = $this->leadModel->getEntities([
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
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        $entity = $this->companyModel->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('lead:leads:create')) {
                $this->throwAccessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($request, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): Response
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
            $entity = $this->companyModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.company.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted('lead:leads:deleteother')) {
                $this->throwAccessDenied();
            } elseif ($this->companyModel->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'lead.company');
            }

            $this->companyModel->deleteEntity($entity);

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
     */
    public function batchDeleteAction(Request $request): Response
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
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $this->companyModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.company.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted('lead:leads:deleteother')) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->companyModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead.company', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if ([] !== $deleteIds) {
                $entities = $this->companyModel->deleteEntities($deleteIds);
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
     * Bulk find and replace company field values.
     */
    public function batchFindReplaceAction(Request $request, CompanyModel $model, CustomFieldFindReplace $findReplace): JsonResponse|Response
    {
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                'lead:leads:editown',
                'lead:leads:editother',
            ],
            'RETURN_ARRAY'
        );

        if (
            (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother'])
            || (!$permissions['lead:leads:editown'] && !$permissions['lead:leads:editother'])
        ) {
            $this->throwAccessDenied();
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->processCompanyFindReplace($request, $model, $findReplace);
        }

        return $this->createCompanyFindReplaceFormResponse($request, $findReplace);
    }

    private function processCompanyFindReplace(Request $request, CompanyModel $model, CustomFieldFindReplace $findReplace): JsonResponse
    {
        $requestData = $request->request->all();
        $data        = $requestData['lead_batch_find_replace'] ?? $requestData['find_replace'] ?? [];
        $ids         = json_decode($data['ids'] ?? '[]', true);
        $fieldAlias  = $data['field'] ?? null;
        $updated     = [];

        if (is_string($fieldAlias) && is_array($ids)) {
            $entities = $this->getCompanyFindReplaceEntities($request, $model, $data, $ids);
            $updated  = $this->replaceCompanyFieldValues($findReplace, $fieldAlias, $data, $entities, $model);

            if ([] !== $updated) {
                $model->saveEntities($updated);
            }
        }

        $this->addFlashMessage(
            'mautic.company.batch_companies_affected',
            [
                '%count%' => count($updated),
            ]
        );

        return new JsonResponse(
            [
                'closeModal' => true,
                'callback'   => 'refreshFindReplaceList',
                'flashes'    => $this->getFlashContent(),
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $ids
     *
     * @return iterable<object>
     */
    private function getCompanyFindReplaceEntities(Request $request, CompanyModel $model, array $data, array $ids): iterable
    {
        if (!empty($data['all'])) {
            return $model->getEntities([
                'filter'           => $this->getCurrentCompanyListFilter($request),
                'ignore_paginator' => true,
            ]);
        }

        return $model->getEntities([
            'filter'           => [
                'force' => [
                    [
                        'column' => 'comp.id',
                        'expr'   => 'in',
                        'value'  => $ids,
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @param iterable<object>     $entities
     *
     * @return array<int, Company>
     */
    private function replaceCompanyFieldValues(CustomFieldFindReplace $findReplace, string $fieldAlias, array $data, iterable $entities, CompanyModel $model): array
    {
        /** @var array<int, Company> $updated */
        $updated = $findReplace->replace(
            new CustomFieldFindReplaceCriteria('company', $fieldAlias, $data['find'] ?? null, $data['replace'] ?? null),
            $entities,
            function (CustomFieldEntityInterface $company, array $values) use ($model): void {
                \assert($company instanceof Company);
                $model->setFieldValues($company, $values, true);
            },
            function (CustomFieldEntityInterface $company): bool {
                \assert($company instanceof Company);

                return $this->security->hasEntityAccess(
                    'lead:leads:editown',
                    'lead:leads:editother',
                    $company->getPermissionUser()
                );
            },
            function (CustomFieldEntityInterface $company) use ($model): ?CustomFieldEntityInterface {
                \assert($company instanceof Company);

                return $model->getEntity($company->getId());
            }
        );

        return $updated;
    }

    private function createCompanyFindReplaceFormResponse(Request $request, CustomFieldFindReplace $findReplace): Response
    {
        $route = $this->generateUrl(
            'mautic_company_action',
            [
                'objectAction' => 'batchFindReplace',
            ]
        );

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->formFactory->createNamed('lead_batch_find_replace', FindReplaceType::class, [], [
                        'action'        => $route,
                        'all_items'     => $request->query->getBoolean('all'),
                        'field_choices' => $findReplace->getFieldChoices('company'),
                        'field_label'   => 'mautic.company.batch.find_replace.field',
                    ])->createView(),
                ],
                'contentTemplate' => '@MauticLead/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'companyBatch',
                    'route'         => $route,
                ],
            ]
        );
    }

    /**
     * @return array<string,array<int,array<string,mixed>>|string>
     */
    private function getCurrentCompanyListFilter(Request $request): array
    {
        return [
            'string' => $request->getSession()->get('mautic.company.filter', ''),
            'force'  => [],
        ];
    }

    /**
     * Company Merge function.
     */
    public function mergeAction(Request $request, $objectId): Response
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                'lead:leads:create',
                'lead:leads:editother',
                'lead:leads:deleteother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
            $this->throwAccessDenied();
        }
        $secondaryCompany = $this->companyModel->getEntity($objectId);
        $page             = $request->getSession()->get('mautic.lead.page', 1);
        $primaryCompany   = null;
        $viewParameters   = [];

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
                'action'              => $action,
                'main_entity'         => $secondaryCompany->getId(),
                'model_lookup_method' => 'getSimpleLookupResults',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $valid = true;
            if (!$this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data           = $form->getData();
                    $primaryMergeId = $data['company_to_merge'];
                    $primaryCompany = $this->companyModel->getEntity($primaryMergeId);

                    if (null === $primaryCompany) {
                        return $this->postActionRedirect(
                            array_merge(
                                $postActionVars,
                                [
                                    'flashes' => [
                                        [
                                            'type'    => 'error',
                                            'msg'     => 'mautic.company.error.notfound',
                                            'msgVars' => ['%id%' => $primaryMergeId],
                                        ],
                                    ],
                                ]
                            )
                        );
                    }
                    if (!$permissions['lead:leads:editother']) {
                        $this->throwAccessDenied();
                    } elseif ($this->companyModel->isLocked($secondaryCompany)) {
                        // deny access if the entity is locked
                        return $this->isLocked($postActionVars, $primaryCompany, 'lead.company');
                    } elseif ($this->companyModel->isLocked($primaryCompany)) {
                        // deny access if the entity is locked
                        return $this->isLocked($postActionVars, $primaryCompany, 'lead.company');
                    }

                    // Both leads are good so now we merge them
                    $this->companyModel->companyMerge($primaryCompany, $secondaryCompany);
                }

                if ($valid) {
                    $this->addFlashMessage(
                        'mautic.company.notice.merged',
                        [
                            '%primary%'   => $primaryCompany->getName(),
                            '%secondary%' => $secondaryCompany->getName(),
                        ]
                    );
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
     */
    #[Route(
        '/s/companies/company/export/{companyId}',
        name: 'mautic_company_export_action',
        requirements: ['companyId' => '\d+'],
    )]
    public function companyExportAction(Request $request, ExportHelper $exportHelper, $companyId): Response|\Symfony\Component\HttpFoundation\StreamedResponse
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
            $this->throwAccessDenied();
        }
        $company       = $this->companyModel->getEntity($companyId);
        $dataType      = $request->get('filetype', 'csv');

        if (!$company instanceof Company) {
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
