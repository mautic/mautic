<?php
/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\Controller;

use Mautic\FormBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Batch;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Person;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FullContactController extends FormController
{

    /**
     * @param string $objectId
     *
     * @return JsonResponse
     */
    public function lookupPersonAction($objectId = '')
    {
        if ('POST' === $this->request->getMethod()) {
            $data = $this->request->request->get('lead_lookup', [], true);
            $objectId = $data['objectId'];
        }
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        $lead = $model->getEntity($objectId);

        if (!$this->get('mautic.security')->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $lead->getPermissionUser()
        )
        ) {
            $this->addFlash(
                $this->translator->trans('mautic.plugin.fullcontact.forbidden'),
                [],
                'error'
            );

            return new JsonResponse(
                [
                    'closeModal' => true,
                    'flashes' => $this->getFlashContent(),
                ]
            );
        }
        
        if ('GET' === $this->request->getMethod()) {

            $route = $this->generateUrl(
                'mautic_plugin_fullcontact_action',
                [
                    'objectAction' => 'lookupPerson',
                ]
            );

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form' => $this->createForm(
                            'lead_lookup',
                            [
                                'objectId' => $objectId,
                            ],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'email' => $lead->getEmail(),
                    ],
                    'contentTemplate' => 'MauticFullContactBundle:FullContact:lookupPerson.html.php',
                    'passthroughVars' => [
                        'activeLink' => '#mautic_contact_index',
                        'mauticContent' => 'lead',
                        'route' => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $this->request->getMethod()) {
                // get api_key from plugin settings
                $integrationHelper = $this->get('mautic.helper.integration');
                $myIntegration = $integrationHelper->getIntegrationObject('FullContact');
                $keys = $myIntegration->getDecryptedApiKeys();
                $fullcontact = new FullContact_Person($keys['apikey']);
                try {

                    $webhookId = 'fullcontact#'.$objectId;

                    if (FALSE === apc_fetch($webhookId)) {
                        $fullcontact->setWebhookUrl(
                            $this->generateUrl(
                                'mautic_plugin_fullcontact_index',
                                [],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                            $webhookId
                        );
                        $res = $fullcontact->lookupByEmailMD5(md5($lead->getEmail()));
                        apc_add($webhookId, $res);
                    }
                    $this->addFlash(
                        'mautic.lead.batch_leads_affected',
                        [
                            'pluralCount' => 1,
                            '%count%' => 1,
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
                        'flashes' => $this->getFlashContent(),
                    ]
                );

            }
        }
    }

    /**
     *
     * @return JsonResponse
     * @throws \MauticPlugin\MauticFullContactBundle\Exception\FullContact_Exception_NoCredit
     */
    public function batchLookupPersonAction()
    {
        $logger = $this->get('monolog.logger.mautic');
        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        if ('GET' === $this->request->getMethod()) {
            $data = $this->request->query->get('lead_batch_lookup', [], true);
        } else {
            $data = $this->request->request->get('lead_batch_lookup', [], true);
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
                                    'expr' => 'in',
                                    'value' => $ids,
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
                ) {
                    $lookupEmails[$lead->getId()] = $lead->getEmail();
                }
            }

            $count = count($lookupEmails);
        }

        if (0 === $count) {
            $this->addFlash(
                $this->translator->trans('mautic.plugin.fullcontact.empty'),
                [],
                'error'
            );

            return new JsonResponse(
                [
                    'closeModal' => true,
                    'flashes' => $this->getFlashContent(),
                ]
            );
        } else {
            if ($count > 20) {
                $this->addFlash(
                    $this->translator->trans('mautic.plugin.fullcontact.toomany'),
                    [],
                    'error'
                );

                return new JsonResponse(
                    [
                        'closeModal' => true,
                        'flashes' => $this->getFlashContent(),
                    ]
                );
            }
        }
        if ('GET' === $this->request->getMethod()) {

            $route = $this->generateUrl(
                'mautic_plugin_fullcontact_action',
                [
                    'objectAction' => 'batchLookupPerson',
                ]
            );

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form' => $this->createForm(
                            'lead_batch_lookup',
                            [],
                            [
                                'action' => $route,
                            ]
                        )->createView(),
                        'lookupEmails' => array_values($lookupEmails),
                    ],
                    'contentTemplate' => 'MauticFullContactBundle:FullContact:batchLookupPerson.html.php',
                    'passthroughVars' => [
                        'activeLink' => '#mautic_contact_index',
                        'mauticContent' => 'leadBatch',
                        'route' => $route,
                    ],
                ]
            );
        } else {
            if ('POST' === $this->request->getMethod()) {
                // get api_key from plugin settings
                $integrationHelper = $this->get('mautic.helper.integration');
                $myIntegration = $integrationHelper->getIntegrationObject('FullContact');
                $keys = $myIntegration->getDecryptedApiKeys();
                $fullcontact = new FullContact_Person($keys['apikey']);
                try {
                    // TODO: batch is not working on fullcontact
//                    $result = $fullcontact->sendRequests(
//                        array_map(
//                            function ($e) {
//                                return 'https://api.fullcontact.com/v2/person.json?emailMD5='.md5(
//                                    $e
//                                ).'&webhookUrl='.urlencode('https://requestbin.fullcontact.com/17kl0v91');
//                            },
//                            $lookupEmails
//                        )
//                    );

                    foreach ($lookupEmails as $id => $lookupEmail) {
                        $webhookId = 'fullcontact#'.$id;
                        if (FALSE === apc_fetch($webhookId)) {
                            $fullcontact->setWebhookUrl(
                                $this->generateUrl(
                                    'mautic_plugin_fullcontact_index',
                                    [],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                ),
                                $webhookId
                            );
                            $res = $fullcontact->lookupByEmailMD5(md5($lookupEmail));
                            apc_add($webhookId, $res);
                        }
                    }

                    $this->addFlash(
                        'mautic.lead.batch_leads_affected',
                        [
                            'pluralCount' => $count,
                            '%count%' => $count,
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
                        'flashes' => $this->getFlashContent(),
                    ]
                );

            }
        }
    }
}