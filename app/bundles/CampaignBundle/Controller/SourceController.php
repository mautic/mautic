<?php

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Form\Type\CampaignLeadSourceType;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SourceController extends CommonFormController
{
    /**
     * @var string[]
     */
    private array $supportedSourceTypes = ['lists', 'forms'];

    /**
     * @var mixed
     */
    private $modifiedSources = [];

    /**
     * @param int $objectId
     *
     * @return Response
     */
    public function newAction(Request $request, $objectId = 0)
    {
        $success = 0;
        $valid   = $cancelled   = false;
        $this->setCampaignElements($request->request);
        if ('1' === $request->request->get('submit')) {
            $source     = $request->request->all()['campaign_leadsource'] ?? [];
            $sourceType = $source['sourceType'];
        } else {
            $sourceType = $request->query->get('sourceType');
            $source     = [
                'sourceType' => $sourceType,
            ];
        }

        // set the sourceType key for sources
        if (!in_array($sourceType, $this->supportedSourceTypes)) {
            return $this->modalAccessDenied();
        }

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->modalAccessDenied();
        }

        $campaignModel = $this->getModel('campaign');
        \assert($campaignModel instanceof CampaignModel);
        $sourceList = $campaignModel->getSourceLists($sourceType);
        $form       = $this->formFactory->create(
            CampaignLeadSourceType::class,
            $source,
            [
                'action'         => $this->generateUrl('mautic_campaignsource_action', ['objectAction' => 'new', 'objectId' => $objectId]),
                'source_choices' => $sourceList,
            ]
        );

        $modifiedSources = $this->modifiedSources;
        // Check for a submitted form and process it
        if ('1' === $request->request->get('submit')) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success                      = 1;
                    $modifiedSources[$sourceType] = array_flip($form[$sourceType]->getData());
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

        if (1 === $success && !empty($modifiedSources)) {
            $passthroughVars['modifiedSources'] = $modifiedSources;
        }

        if ($cancelled || $valid) {
            if ($valid) {
                $passthroughVars['sourceHtml'] = $this->renderView(
                    '@MauticCampaign/Source/_index.html.twig',
                    [
                        'sourceType' => $sourceType,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $modifiedSources[$sourceType])),
                    ]
                );
                $passthroughVars['sourceType'] = $sourceType;
            }

            // just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        } else {
            $viewParams = [
                'sourceType' => $sourceType,
                'form'       => $form->createView(),
            ];

            return $this->ajaxAction(
                $request,
                [
                    'contentTemplate' => '@MauticCampaign/Source/form.html.twig',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }
    }

    /**
     * @return Response
     */
    public function editAction(Request $request, $objectId)
    {
        $this->setCampaignElements($request->request);
        $modifiedSources = $this->modifiedSources;

        if ('1' === $request->request->get('submit')) {
            $source     = $request->request->all()['campaign_leadsource'] ?? [];
            $sourceType = $source['sourceType'];
        } else {
            $sourceType = $request->query->get('sourceType');
            $source     = [
                'sourceType' => $sourceType,
                $sourceType  => array_flip($modifiedSources[$sourceType]),
            ];
        }

        $success = 0;
        $valid   = $cancelled   = false;

        if (!in_array($sourceType, $this->supportedSourceTypes)) {
            return $this->modalAccessDenied();
        }

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->modalAccessDenied();
        }

        $campaignModel = $this->getModel('campaign');
        \assert($campaignModel instanceof CampaignModel);
        $sourceList = $campaignModel->getSourceLists($sourceType);
        $form       = $this->formFactory->create(
            CampaignLeadSourceType::class,
            $source,
            [
                'action'         => $this->generateUrl('mautic_campaignsource_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                'source_choices' => $sourceList,
            ]
        );

        // Check for a submitted form and process it
        if ('1' === $request->request->get('submit')) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // save the properties to session
                    $modifiedSources[$sourceType] = array_flip($form[$sourceType]->getData());
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

        if (1 === $success && !empty($modifiedSources)) {
            $passthroughVars['modifiedSources'] = $modifiedSources;
        }

        if ($cancelled || $valid) {
            if ($valid) {
                $passthroughVars['updateHtml'] = $this->renderView(
                    '@MauticCampaign/Source/_index.html.twig',
                    [
                        'sourceType' => $sourceType,
                        'campaignId' => $objectId,
                        'names'      => implode(', ', array_intersect_key($sourceList, $modifiedSources[$sourceType])),
                        'update'     => true,
                    ]
                );
                $passthroughVars['sourceType'] = $sourceType;
            }

            // just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        } else {
            $viewParams = [
                'sourceType' => $sourceType,
                'form'       => $form->createView(),
            ];

            return $this->ajaxAction(
                $request,
                [
                    'contentTemplate' => '@MauticCampaign/Source/form.html.twig',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }
    }

    /**
     * Deletes the entity.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        $this->setCampaignElements($request->request);
        $modifiedSources = $this->modifiedSources;
        $sourceType      = $request->get('sourceType');

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->accessDenied();
        }

        if ('POST' == $request->getMethod()) {
            // Add the field to the delete list
            if (isset($modifiedSources[$sourceType])) {
                unset($modifiedSources[$sourceType]);
            }

            $dataArray = [
                'mauticContent'     => 'campaignSource',
                'success'           => 1,
                'route'             => false,
                'sourceType'        => $sourceType,
                'deleted'           => 1,
                'modifiedSources'   => $modifiedSources,
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }

    private function setCampaignElements(ParameterBag $request): void
    {
        if ($request->get('modifiedSources')) {
            $this->modifiedSources = json_decode($request->get('modifiedSources'), true);
        }
    }
}
