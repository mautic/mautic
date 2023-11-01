<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Form\Type\CompanyMergeType;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends FormController
{
    use LeadDetailsTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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

        /** @var PageHelperFactoryInterface $pageHelperFacotry */
        $pageHelperFacotry = $this->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFacotry->make('mautic.company', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $this->request->get('search', $this->get('session')->get('mautic.company.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $this->get('session')->get('mautic.company.orderby', 'comp.companyname');
        $orderByDir = $this->get('session')->get('mautic.company.orderbydir', 'ASC');

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

        $this->get('session')->set('mautic.company.filter', $search);

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
                    'contentTemplate' => 'MauticLeadBundle:Company:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'company',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        $model      = $this->getModel('lead.company');
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
                'contentTemplate' => 'MauticLeadBundle:Company:list.html.php',
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
    public function contactsListAction($objectId, $page = 1)
    {
        if (empty($objectId)) {
            return $this->accessDenied();
        }

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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

        $leadsIds = 'ids:';
        foreach ($contacts as $contact) {
            $leadsIds .= $contact['lead_id'].',';
        }
        $leadsIds = substr($leadsIds, 0, -1);

        $data = $this->getCompanyContacts($objectId, $page, $leadsIds);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'company'     => $company,
                    'page'        => $data['page'],
                    'contacts'    => $data['items'],
                    'totalItems'  => $data['count'],
                    'limit'       => $data['limit'],
                    'permissions' => $permissions,
                    'security'    => $this->get('mautic.security'),
                ],
                'contentTemplate' => 'MauticLeadBundle:Company:list_rows_contacts.html.php',
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
    public function newAction($entity = null)
    {
        $model = $this->getModel('lead.company');

        if (!($entity instanceof Company)) {
            /** @var \Mautic\LeadBundle\Entity\Company $entity */
            $entity = $model->getEntity();
        }

        if (!$this->get('mautic.security')->isGranted('lead:leads:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page         = $this->get('session')->get('mautic.company.page', 1);
        $method       = $this->request->getMethod();
        $action       = $this->generateUrl('mautic_company_action', ['objectAction' => 'new']);
        $company      = $this->request->request->get('company', []);
        $updateSelect = InputHelper::clean(
            'POST' === $method
                ? ($company['updateSelect'] ?? false)
                : $this->request->get('updateSelect', false)
        );

        $fields = $this->getModel('lead.field')->getPublishedFieldArrays('company');
        $form   = $model->createForm($entity, $this->get('form.factory'), $action, ['fields' => $fields, 'update_select' => $updateSelect]);

        $viewParameters = ['page' => $page];
        $returnUrl      = $this->generateUrl('mautic_company_index', $viewParameters);
        $template       = 'MauticLeadBundle:Company:index';

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    //get custom field values
                    $data = $this->request->request->get('company');
                    //pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }
                    $model->setFieldValues($entity, $data, true);
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash(
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

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                        $template  = 'MauticLeadBundle:Company:index';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
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

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
        $template = 'MauticLeadBundle:Company:form_'.($this->request->get('modal', false) ? 'embedded' : 'standalone').'.html.php';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'   => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'fields' => $fields,
                    'groups' => $groups,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'updateSelect'  => ('POST' == $this->request->getMethod()) ? $updateSelect : null,
                    'route'         => $this->generateUrl(
                        'mautic_company_action',
                        [
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
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
    public function editAction($objectId, $ignorePost = false)
    {
        $model  = $this->getModel('lead.company');
        $entity = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.company.page', 1);

        $viewParameters = ['page' => $page];

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticLeadBundle:Company:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        //form not found
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $entity->getOwner())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'lead.company');
        }

        $action       = $this->generateUrl('mautic_company_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $method       = $this->request->getMethod();
        $company      = $this->request->request->get('company', []);
        $updateSelect = 'POST' === $method
            ? ($company['updateSelect'] ?? false)
            : $this->request->get('updateSelect', false);

        $fields = $this->getModel('lead.field')->getPublishedFieldArrays('company');
        $form   = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            ['fields' => $fields, 'update_select' => $updateSelect]
        );

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data = $this->request->request->get('company');
                    //pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $data[$f->getName()] = $f->getData();
                    }

                    $model->setFieldValues($entity, $data, true);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
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

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                        $template  = 'MauticLeadBundle:Company:index';
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                $template  = 'MauticLeadBundle:Company:index';
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

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
                $form    = $model->createForm($company, $this->get('form.factory'), $action, ['fields' => $fields, 'update_select' => $updateSelect]);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $fields = $model->organizeFieldsByGroup($fields);
        $groups = array_keys($fields);
        sort($groups);
        $template = 'MauticLeadBundle:Company:form_'.($this->request->get('modal', false) ? 'embedded' : 'standalone').'.html.php';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'   => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'fields' => $fields,
                    'groups' => $groups,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'updateSelect'  => InputHelper::clean($this->request->query->get('updateSelect')),
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
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var CompanyModel $model */
        $model  = $this->getModel('lead.company');

        // When we change company data these changes get cached
        // so we need to clear the entity manager
        $model->getRepository()->clear();

        /** @var \Mautic\LeadBundle\Entity\Company $company */
        $company = $model->getEntity($objectId);

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticLeadBundle:Company:index',
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

        if (!$this->get('mautic.security')->hasEntityAccess(
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

        $leadsIds = 'ids:';
        foreach ($contacts as $contact) {
            $leadsIds .= $contact['lead_id'].',';
        }
        $leadsIds = substr($leadsIds, 0, -1);

        $engagementData = is_array($contacts) ? $this->getCompanyEngagementsForGraph($contacts) : [];

        $contacts = $this->getCompanyContacts($objectId, null, $leadsIds);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'company'           => $company,
                    'fields'            => $fields,
                    'items'             => $contacts['items'],
                    'permissions'       => $permissions,
                    'engagementData'    => $engagementData,
                    'security'          => $this->get('mautic.security'),
                    'page'              => $contacts['page'],
                    'totalItems'        => $contacts['count'],
                    'limit'             => $contacts['limit'],
                ],
                'contentTemplate' => 'MauticLeadBundle:Company:company.html.php',
            ]
        );
    }

    /**
     * Get company's contacts for company view.
     *
     * @param int    $companyId
     * @param int    $page
     * @param string $leadsIds  filter to get only company's contacts
     *
     * @return array
     */
    public function getCompanyContacts($companyId, $page = 0, $leadsIds = '')
    {
        $this->setListFilters();

        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model   = $this->getModel('lead');
        $session = $this->get('session');
        //set limits
        $limit = $session->get('mautic.company.'.$companyId.'.contacts.limit', $this->get('mautic.helper.core_parameters')->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        //do some default filtering
        $orderBy    = $session->get('mautic.company.'.$companyId.'.contacts.orderby', 'l.last_active');
        $orderByDir = $session->get('mautic.company.'.$companyId.'.contacts.orderbydir', 'DESC');

        $results = $model->getEntities([
            'start'          => $start,
            'limit'          => $limit,
            'filter'         => ['string' => $leadsIds],
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
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('lead.company');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->get('mautic.security')->isGranted('lead:leads:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.company.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticLeadBundle:Company:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model  = $this->getModel('lead.company');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.company.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->isGranted('lead:leads:deleteother')) {
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
        } //else don't do anything

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.company.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticLeadBundle:Company:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model     = $this->getModel('lead.company');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
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
                } elseif (!$this->get('mautic.security')->isGranted('lead:leads:deleteother')) {
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
                $this->addFlash(
                    'mautic.company.notice.batch_deleted',
                    [
                        '%count%'     => $deleted,
                    ]
                );
            }
        } //else don't do anything

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
     * @param $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function mergeAction($objectId)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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
        $page             = $this->get('session')->get('mautic.lead.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticLeadBundle:Company:index',
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
                                'msg'     => 'mautic.lead.company.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }

        $action = $this->generateUrl('mautic_company_action', ['objectAction' => 'merge', 'objectId' => $secondaryCompany->getId()]);

        $form = $this->get('form.factory')->create(
            CompanyMergeType::class,
            [],
            [
                'action'      => $action,
                'main_entity' => $secondaryCompany->getId(),
            ]
        );

        if ('POST' == $this->request->getMethod()) {
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
                                            'msg'     => 'mautic.lead.company.error.notfound',
                                            'msgVars' => ['%id%' => $primaryCompany->getId()],
                                        ],
                                    ],
                                ]
                            )
                        );
                    } elseif (!$permissions['lead:leads:editother']) {
                        return $this->accessDenied();
                    } elseif ($model->isLocked($secondaryCompany)) {
                        //deny access if the entity is locked
                        return $this->isLocked($postActionVars, $primaryCompany, 'lead.company');
                    } elseif ($model->isLocked($primaryCompany)) {
                        //deny access if the entity is locked
                        return $this->isLocked($postActionVars, $primaryCompany, 'lead.company');
                    }

                    //Both leads are good so now we merge them
                    $mainCompany = $model->companyMerge($primaryCompany, $secondaryCompany, false);
                }

                if ($valid) {
                    $viewParameters = [
                        'objectId'     => $primaryCompany->getId(),
                        'objectAction' => 'edit',
                    ];
                }
            } else {
                $viewParameters = [
                    'objectId'     => $secondaryCompany->getId(),
                    'objectAction' => 'edit',
                ];
            }

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $this->generateUrl('mautic_company_action', $viewParameters),
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'MauticLeadBundle:Company:edit',
                    'passthroughVars' => [
                        'closeModal' => 1,
                    ],
                ]
            );
        }

        $tmpl = $this->request->get('tmpl', 'index');

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
                'contentTemplate' => 'MauticLeadBundle:Company:merge.html.php',
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
     * @param $companyId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function companyExportAction($companyId)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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
        $dataType      = $this->request->get('filetype', 'csv');

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

        return $this->exportResultsAs($export, $dataType, 'company_data_'.($companyFields['companyemail'] ?: $companyFields['id']));
    }
}
