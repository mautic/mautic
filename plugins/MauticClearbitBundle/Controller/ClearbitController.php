<?php
/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticClearbitBundle\Controller;

use Mautic\FormBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Company;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Person;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function lookupPersonAction($objectId = '')
    {
        if ('POST' === $this->request->getMethod()) {
            $data     = $this->request->request->get('clearbit_lookup', [], true);
            $objectId = $data['objectId'];
        }
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        $lead  = $model->getEntity($objectId);

        if (!$this->get('mautic.security')->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $lead->getPermissionUser()
        )
        ) {
            $this->addFlash(
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

        if ('GET' === $this->request->getMethod()) {
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
                            'clearbit_lookup',
                            [
                                'objectId' => $objectId,
                            ],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItem' => $lead->getEmail(),
                    ],
                    'contentTemplate' => 'MauticClearbitBundle:Clearbit:lookup.html.php',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_contact_index',
                        'mauticContent' => 'lead',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $this->request->getMethod()) {
                // get api_key from plugin settings
                $integrationHelper = $this->get('mautic.helper.integration');
                /** @var ClearbitIntegration $myIntegration */
                $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');
                $keys          = $myIntegration->getDecryptedApiKeys();
                $clearbit      = new Clearbit_Person($keys['apikey']);
                try {
                    $webhookId = sprintf(
                        'clearbit%s#%s#%d',
                        (array_key_exists('notify', $data) && $data['notify']) ? '_notify' : '',
                        $objectId,
                        $this->user->getId()
                    );

                    $cache   = $lead->getSocialCache();
                    $cacheId = sprintf('%s%s', $webhookId, date('YmdH'));
                    if (!array_key_exists($cacheId, $cache)) {
                        $clearbit->setWebhookId($webhookId);
                        $res             = $clearbit->lookupByEmail($lead->getEmail());
                        $cache[$cacheId] = serialize($res);
                        $lead->setSocialCache($cache);
                        $model->getRepository()->saveEntity($lead);
                    }
                    $this->addFlash(
                        'mautic.lead.batch_leads_affected',
                        [
                            'pluralCount' => 1,
                            '%count%'     => 1,
                        ]
                    );
                } catch (\Exception $ex) {
                    $this->addFlash(
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
    public function batchLookupPersonAction()
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        if ('GET' === $this->request->getMethod()) {
            $data = $this->request->query->get('clearbit_batch_lookup', [], true);
        } else {
            $data = $this->request->request->get('clearbit_batch_lookup', [], true);
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
                if ($this->get('mautic.security')->hasEntityAccess(
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
            $this->addFlash(
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
                $this->addFlash(
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
        if ('GET' === $this->request->getMethod()) {
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
                            'clearbit_batch_lookup',
                            [],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItems' => array_values($lookupEmails),
                    ],
                    'contentTemplate' => 'MauticClearbitBundle:Clearbit:batchLookup.html.php',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_contact_index',
                        'mauticContent' => 'leadBatch',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $this->request->getMethod()) {
                // get api_key from plugin settings
                $integrationHelper = $this->get('mautic.helper.integration');
                /** @var ClearbitIntegration $myIntegration */
                $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');
                $keys          = $myIntegration->getDecryptedApiKeys();
                $clearbit      = new Clearbit_Person($keys['apikey']);
                try {
                    foreach ($lookupEmails as $id => $lookupEmail) {
                        $lead      = $model->getEntity($id);
                        $webhookId = sprintf(
                            'clearbit%s#%s#%d',
                            (array_key_exists('notify', $data) && $data['notify']) ? '_notify' : '',
                            $id,
                            $this->user->getId()
                        );
                        $cache   = $lead->getSocialCache();
                        $cacheId = sprintf('%s%s', $webhookId, date('YmdH'));
                        if (!array_key_exists($cacheId, $cache)) {
                            $clearbit->setWebhookId($webhookId);
                            $res             = $clearbit->lookupByEmail($lookupEmail);
                            $cache[$cacheId] = serialize($res);
                            $lead->setSocialCache($cache);
                            $model->getRepository()->saveEntity($lead);
                        }
                    }

                    $this->addFlash(
                        'mautic.lead.batch_leads_affected',
                        [
                            'pluralCount' => $count,
                            '%count%'     => $count,
                        ]
                    );
                } catch (\Exception $ex) {
                    $this->addFlash(
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

    /***************** COMPANY ***********************/

    /**
     * @param string $objectId
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function lookupCompanyAction($objectId = '')
    {
        if ('POST' === $this->request->getMethod()) {
            $data     = $this->request->request->get('clearbit_lookup', [], true);
            $objectId = $data['objectId'];
        }
        /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
        $model = $this->getModel('lead.company');
        /** @var Company $company */
        $company = $model->getEntity($objectId);

        if ('GET' === $this->request->getMethod()) {
            $route = $this->generateUrl(
                'mautic_plugin_clearbit_action',
                [
                    'objectAction' => 'lookupCompany',
                ]
            );

            $website = $company->getFieldValue('companywebsite');

            if (!$website) {
                $this->addFlash(
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
                            'clearbit_lookup',
                            [
                                'objectId' => $objectId,
                            ],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItem' => $parse['host'],
                    ],
                    'contentTemplate' => 'MauticClearbitBundle:Clearbit:lookup.html.php',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'company',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $this->request->getMethod()) {
                // get api_key from plugin settings
                $integrationHelper = $this->get('mautic.helper.integration');
                /** @var ClearbitIntegration $myIntegration */
                $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');
                $keys          = $myIntegration->getDecryptedApiKeys();
                $clearbit      = new Clearbit_Company($keys['apikey']);
                try {
                    $webhookId = sprintf(
                        'clearbitcomp%s#%s#%d',
                        (array_key_exists('notify', $data) && $data['notify']) ? '_notify' : '',
                        $objectId,
                        $this->user->getId()
                    );
                    $website = $company->getFieldValue('companywebsite');
                    $parse   = parse_url($website);
                    $cache   = $company->getSocialCache();
                    $cacheId = sprintf('%s%s', $webhookId, date('YmdH'));
                    if (!array_key_exists($cacheId, $cache) && isset($parse['host'])) {
                        $clearbit->setWebhookId($webhookId);
                        $res             = $clearbit->lookupByDomain($parse['host']);
                        $cache[$cacheId] = serialize($res);
                        $company->setSocialCache($cache);
                        $model->getRepository()->saveEntity($company);
                    }
                    $this->addFlash(
                        'mautic.company.batch_companies_affected',
                        [
                            'pluralCount' => 1,
                            '%count%'     => 1,
                        ]
                    );
                } catch (\Exception $ex) {
                    $this->addFlash(
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
    public function batchLookupCompanyAction()
    {
        /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
        $model = $this->getModel('lead.company');
        if ('GET' === $this->request->getMethod()) {
            $data = $this->request->query->get('clearbit_batch_lookup', [], true);
        } else {
            $data = $this->request->request->get('clearbit_batch_lookup', [], true);
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
            $this->addFlash(
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
                $this->addFlash(
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
        if ('GET' === $this->request->getMethod()) {
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
                            'clearbit_batch_lookup',
                            [],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupItems' => array_values($lookupWebsites),
                    ],
                    'contentTemplate' => 'MauticClearbitBundle:Clearbit:batchLookup.html.php',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'companyBatch',
                        'route'         => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $this->request->getMethod()) {
                // get api_key from plugin settings
                $integrationHelper = $this->get('mautic.helper.integration');
                /** @var ClearbitIntegration $myIntegration */
                $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');
                $keys          = $myIntegration->getDecryptedApiKeys();
                $clearbit      = new Clearbit_Company($keys['apikey']);
                try {
                    foreach ($lookupWebsites as $id => $lookupWebsite) {
                        $company   = $model->getEntity($id);
                        $webhookId = sprintf(
                            'clearbitcomp%s#%s#%d',
                            (array_key_exists('notify', $data) && $data['notify']) ? '_notify' : '',
                            $id,
                            $this->user->getId()
                        );
                        $cache   = $company->getSocialCache();
                        $cacheId = sprintf('%s%s', $webhookId, date('YmdH'));
                        if (!array_key_exists($cacheId, $cache)) {
                            $clearbit->setWebhookId($webhookId);
                            $res             = $clearbit->lookupByDomain($lookupWebsite);
                            $cache[$cacheId] = serialize($res);
                            $company->setSocialCache($cache);
                            $model->getRepository()->saveEntity($company);
                        }
                    }

                    $this->addFlash(
                        'mautic.company.batch_companies_affected',
                        [
                            'pluralCount' => $count,
                            '%count%'     => $count,
                        ]
                    );
                } catch (\Exception $ex) {
                    $this->addFlash(
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
}
