<?php

namespace MauticPlugin\MauticClearbitBundle\Controller;

use Mautic\FormBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticClearbitBundle\Form\Type\BatchLookupType;
use MauticPlugin\MauticClearbitBundle\Form\Type\LookupType;
use MauticPlugin\MauticClearbitBundle\Helper\LookupHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ClearbitController extends FormController
{
    /**
     * @param string $objectId
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function lookupPersonAction(Request $request, LookupHelper $lookupHelper, $objectId = '')
    {
        if ('POST' === $request->getMethod()) {
            $data     = $request->request->all()['clearbit_lookup'] ?? [];
            $objectId = $data['objectId'];
        }
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        $lead  = $model->getEntity($objectId);

        if (!$this->security->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $lead->getPermissionUser()
        )
        ) {
            $this->addFlashMessage(
                $this->translator->trans('mautic.plugin.clearbit.forbidden'),
                [],
                'error'
            );

            return new JsonResponse(
                [
                    'closeModal' => true,
                    'flashes'    => $this->getFlashContent(),
                ]
            );
        }

        if ('GET' === $request->getMethod()) {
            $route = $this->generateUrl(
                'mautic_plugin_clearbit_action',
                [
                    'objectAction' => 'lookupPerson',
                ]
            );

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form' => $this->createForm(
                            LookupType::class,
                            [
                                'objectId' => $objectId,
                            ],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItem' => $lead->getEmail(),
                    ],
                    'contentTemplate' => '@MauticClearbit/Clearbit/lookup.html.twig',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_contact_index',
                        'mauticContent' => 'lead',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $request->getMethod()) {
                try {
                    $lookupHelper->lookupContact($lead, array_key_exists('notify', $data));
                    $this->addFlashMessage(
                        'mautic.lead.batch_leads_affected',
                        [
                            '%count%'     => 1,
                        ]
                    );
                } catch (\Exception $ex) {
                    $this->addFlashMessage(
                        $ex->getMessage(),
                        [],
                        'error'
                    );
                }

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes'    => $this->getFlashContent(),
                    ]
                );
            }
        }

        return new Response('Bad Request', 400);
    }

    /**
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function batchLookupPersonAction(Request $request, LookupHelper $lookupHelper)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        if ('GET' === $request->getMethod()) {
            $data = $request->query->all()['clearbit_batch_lookup'] ?? [];
        } else {
            $data = $request->request->all()['clearbit_batch_lookup'] ?? [];
        }

        $entities = [];
        if (array_key_exists('ids', $data)) {
            $ids = $data['ids'];

            if (!is_array($ids)) {
                $ids = json_decode($ids, true);
            }

            if (is_array($ids) && count($ids)) {
                $entities = $model->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => 'l.id',
                                    'expr'   => 'in',
                                    'value'  => $ids,
                                ],
                            ],
                        ],
                        'ignore_paginator' => true,
                    ]
                );
            }
        }

        $lookupEmails = [];
        if ($count = count($entities)) {
            /** @var Lead $lead */
            foreach ($entities as $lead) {
                if ($this->security->hasEntityAccess(
                    'lead:leads:editown',
                    'lead:leads:editother',
                    $lead->getPermissionUser()
                )
                    && $lead->getEmail()
                ) {
                    $lookupEmails[$lead->getId()] = $lead->getEmail();
                }
            }

            $count = count($lookupEmails);
        }

        if (0 === $count) {
            $this->addFlashMessage(
                $this->translator->trans('mautic.plugin.clearbit.empty'),
                [],
                'error'
            );

            return new JsonResponse(
                [
                    'closeModal' => true,
                    'flashes'    => $this->getFlashContent(),
                ]
            );
        } else {
            if ($count > 20) {
                $this->addFlashMessage(
                    $this->translator->trans('mautic.plugin.clearbit.toomany'),
                    [],
                    'error'
                );

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes'    => $this->getFlashContent(),
                    ]
                );
            }
        }
        if ('GET' === $request->getMethod()) {
            $route = $this->generateUrl(
                'mautic_plugin_clearbit_action',
                [
                    'objectAction' => 'batchLookupPerson',
                ]
            );

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form' => $this->createForm(
                            BatchLookupType::class,
                            [],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItems' => array_values($lookupEmails),
                    ],
                    'contentTemplate' => '@MauticClearbit/Clearbit/batchLookup.html.twig',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_contact_index',
                        'mauticContent' => 'leadBatch',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $request->getMethod()) {
                $notify = array_key_exists('notify', $data);
                foreach ($lookupEmails as $id => $lookupEmail) {
                    if ($lead = $model->getEntity($id)) {
                        try {
                            $lookupHelper->lookupContact($lead, $notify);
                        } catch (\Exception $ex) {
                            $this->addFlashMessage(
                                $ex->getMessage(),
                                [],
                                'error'
                            );
                            --$count;
                        }
                    }
                }

                if ($count) {
                    $this->addFlashMessage(
                        'mautic.lead.batch_leads_affected',
                        [
                            '%count%'     => $count,
                        ]
                    );
                }

                return new JsonResponse(
                    [
                        'closeModal' => true,
                         'flashes'   => $this->getFlashContent(),
                    ]
                );
            }
        }

        return new Response('Bad Request', 400);
    }

    /***************** COMPANY ***********************/

    /**
     * @param string $objectId
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function lookupCompanyAction(Request $request, LookupHelper $lookupHelper, $objectId = '')
    {
        if ('POST' === $request->getMethod()) {
            $data     = $request->request->all()['clearbit_lookup'] ?? [];
            $objectId = $data['objectId'];
        }
        /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
        $model = $this->getModel('lead.company');
        /** @var Company $company */
        $company = $model->getEntity($objectId);

        if ('GET' === $request->getMethod()) {
            $route = $this->generateUrl(
                'mautic_plugin_clearbit_action',
                [
                    'objectAction' => 'lookupCompany',
                ]
            );

            $website = $company->getFieldValue('companywebsite');

            if (!$website) {
                $this->addFlashMessage(
                    $this->translator->trans('mautic.plugin.clearbit.compempty'),
                    [],
                    'error'
                );

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes'    => $this->getFlashContent(),
                    ]
                );
            }
            $parse = parse_url($website);

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form' => $this->createForm(
                            LookupType::class,
                            [
                                'objectId' => $objectId,
                            ],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItem' => $parse['host'],
                    ],
                    'contentTemplate' => '@MauticClearbit/Clearbit/lookup.html.twig',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'company',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $request->getMethod()) {
                try {
                    $lookupHelper->lookupCompany($company, array_key_exists('notify', $data));
                    $this->addFlashMessage(
                        'mautic.company.batch_companies_affected',
                        [
                            '%count%'     => 1,
                        ]
                    );
                } catch (\Exception $ex) {
                    $this->addFlashMessage(
                        $ex->getMessage(),
                        [],
                        'error'
                    );
                }

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes'    => $this->getFlashContent(),
                    ]
                );
            }
        }

        return new Response('Bad Request', 400);
    }

    /**
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function batchLookupCompanyAction(Request $request, LookupHelper $lookupHelper)
    {
        /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
        $model = $this->getModel('lead.company');
        if ('GET' === $request->getMethod()) {
            $data = $request->query->all()['clearbit_batch_lookup'] ?? [];
        } else {
            $data = $request->request->all()['clearbit_batch_lookup'] ?? [];
        }

        $entities = [];
        if (array_key_exists('ids', $data)) {
            $ids = $data['ids'];

            if (!is_array($ids)) {
                $ids = json_decode($ids, true);
            }

            if (is_array($ids) && count($ids)) {
                $entities = $model->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => 'comp.id',
                                    'expr'   => 'in',
                                    'value'  => $ids,
                                ],
                            ],
                        ],
                        'ignore_paginator' => true,
                    ]
                );
            }
        }

        $lookupWebsites = [];
        if ($count = count($entities)) {
            /** @var Company $company */
            foreach ($entities as $company) {
                if ($company->getFieldValue('companywebsite')) {
                    $website = $company->getFieldValue('companywebsite');
                    $parse   = parse_url($website);
                    if (!isset($parse['host'])) {
                        continue;
                    }
                    $lookupWebsites[$company->getId()] = $parse['host'];
                }
            }

            $count = count($lookupWebsites);
        }

        if (0 === $count) {
            $this->addFlashMessage(
                $this->translator->trans('mautic.plugin.clearbit.compempty'),
                [],
                'error'
            );

            return new JsonResponse(
                [
                    'closeModal' => true,
                    'flashes'    => $this->getFlashContent(),
                ]
            );
        } else {
            if ($count > 20) {
                $this->addFlashMessage(
                    $this->translator->trans('mautic.plugin.clearbit.comptoomany'),
                    [],
                    'error'
                );

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes'    => $this->getFlashContent(),
                    ]
                );
            }
        }
        if ('GET' === $request->getMethod()) {
            $route = $this->generateUrl(
                'mautic_plugin_clearbit_action',
                [
                    'objectAction' => 'batchLookupCompany',
                ]
            );

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form' => $this->createForm(
                            BatchLookupType::class,
                            [],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItems' => array_values($lookupWebsites),
                    ],
                    'contentTemplate' => '@MauticClearbit/Clearbit/batchLookup.html.twig',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'companyBatch',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $request->getMethod()) {
                $notify = array_key_exists('notify', $data);
                foreach ($lookupWebsites as $id => $lookupWebsite) {
                    if ($company = $model->getEntity($id)) {
                        try {
                            $lookupHelper->lookupCompany($company, $notify);
                        } catch (\Exception $ex) {
                            $this->addFlashMessage(
                                $ex->getMessage(),
                                [],
                                'error'
                            );
                            --$count;
                        }
                    }
                }

                if ($count) {
                    $this->addFlashMessage(
                        'mautic.company.batch_companies_affected',
                        [
                            '%count%'     => $count,
                        ]
                    );
                }

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes'    => $this->getFlashContent(),
                    ]
                );
            }
        }

        return new Response('Bad Request', 400);
    }
}
