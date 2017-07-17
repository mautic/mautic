<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Entity\Source;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SourceController extends CommonFormController
{
    private $supportedSourceTypes = ['lists', 'forms'];

    /**
     * @param int $objectId
     *
     * @return JsonResponse
     */
    public function newAction($objectId = 0)
    {
        $success = 0;
        $valid   = $cancelled   = false;
        $method  = $this->request->getMethod();
        $session = $this->get('session');
        if ($method == 'POST') {
            $source     = $this->request->request->get('campaign_leadsource');
            $sourceType = $source['sourceType'];
        } else {
            $sourceType = $this->request->query->get('sourceType');
            $source     = [
                'sourceType' => $sourceType,
            ];
        }

        //set the sourceType key for sources
        if (!in_array($sourceType, $this->supportedSourceTypes)) {
            return $this->modalAccessDenied();
        }

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest()
            || !$this->get('mautic.security')->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->modalAccessDenied();
        }

        $sourceList = $this->getModel('campaign')->getSourceLists($sourceType);
        $form       = $this->get('form.factory')->create(
            'campaign_leadsource',
            $source,
            [
                'action'         => $this->generateUrl('mautic_campaignsource_action', ['objectAction' => 'new', 'objectId' => $objectId]),
                'source_choices' => $sourceList,
            ]
        );

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    $modifiedSources              = $session->get('mautic.campaign.'.$objectId.'.leadsources.modified');
                    $modifiedSources[$sourceType] = array_flip($form[$sourceType]->getData());
                    $session->set('mautic.campaign.'.$objectId.'.leadsources.modified', $modifiedSources);
                } else {
                    $success = 0;
                }
            }
        }

        $passthroughVars = [
            'mauticContent' => 'campaignSource',
            'success'       => $success,
            'route'         => false,
        ];

        if ($cancelled || $valid) {
            if ($valid) {
                $passthroughVars['sourceHtml'] = $this->renderView(
                    'MauticCampaignBundle:Source:index.html.php',
                    [
                        'sourceType' => $sourceType,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $modifiedSources[$sourceType])),
                    ]
                );
                $passthroughVars['sourceType'] = $sourceType;
            }

            //just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        } else {
            $viewParams = [
                'sourceType' => $sourceType,
                'form'       => $form->createView(),
            ];

            return $this->ajaxAction(
                [
                    'contentTemplate' => 'MauticCampaignBundle:Source:form.html.php',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }
    }

    /**
     * @param $objectId
     *
     * @return JsonResponse
     */
    public function editAction($objectId)
    {
        $session         = $this->get('session');
        $method          = $this->request->getMethod();
        $selectedSources = $session->get('mautic.campaign.'.$objectId.'.leadsources.modified', []);
        if ($method == 'POST') {
            $source     = $this->request->request->get('campaign_leadsource');
            $sourceType = $source['sourceType'];
        } else {
            $sourceType = $this->request->query->get('sourceType');
            $source     = [
                'sourceType' => $sourceType,
                $sourceType  => array_flip($selectedSources[$sourceType]),
            ];
        }

        $success = 0;
        $valid   = $cancelled   = false;

        if (!in_array($sourceType, $this->supportedSourceTypes)) {
            return $this->modalAccessDenied();
        }

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest()
            || !$this->get('mautic.security')->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->modalAccessDenied();
        }

        $sourceList = $this->getModel('campaign')->getSourceLists($sourceType);
        $form       = $this->get('form.factory')->create(
            'campaign_leadsource',
            $source,
            [
                'action'         => $this->generateUrl('mautic_campaignsource_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                'source_choices' => $sourceList,
            ]
        );

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //save the properties to session
                    $modifiedSources[$sourceType] = array_flip($form[$sourceType]->getData());
                    $session->set('mautic.campaign.'.$objectId.'.leadsources.modified', $modifiedSources);
                } else {
                    $success = 0;
                }
            }
        }

        $passthroughVars = [
            'mauticContent' => 'campaignSource',
            'success'       => $success,
            'route'         => false,
        ];

        if ($cancelled || $valid) {
            if ($valid) {
                $passthroughVars['updateHtml'] = $this->renderView(
                    'MauticCampaignBundle:Source:index.html.php',
                    [
                        'sourceType' => $sourceType,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $modifiedSources[$sourceType])),
                        'update'     => true,
                    ]
                );
                $passthroughVars['sourceType'] = $sourceType;
            }

            //just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        } else {
            $viewParams = [
                'sourceType' => $sourceType,
                'form'       => $form->createView(),
            ];

            return $this->ajaxAction(
                [
                    'contentTemplate' => 'MauticCampaignBundle:Source:form.html.php',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }
    }

    /**
     * Deletes the entity.
     *
     * @param   $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $session         = $this->get('session');
        $modifiedSources = $session->get('mautic.campaign.'.$objectId.'.leadsources.modified', []);
        $sourceType      = $this->request->get('sourceType');

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest()
            || !$this->get('mautic.security')->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            // Add the field to the delete list
            if (isset($modifiedSources[$sourceType])) {
                unset($modifiedSources[$sourceType]);
                $session->set('mautic.campaign.'.$objectId.'.leadsources.modified', $modifiedSources);
            }

            $dataArray = [
                'mauticContent' => 'campaignSource',
                'success'       => 1,
                'route'         => false,
                'sourceType'    => $sourceType,
                'deleted'       => 1,
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        $response = new JsonResponse($dataArray);

        return $response;
    }
}
