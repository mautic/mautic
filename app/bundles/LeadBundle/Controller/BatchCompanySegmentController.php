<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Form\Type\CompanyBatchType;
use Mautic\LeadBundle\Model\CompanySegmentActionModel;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchCompanySegmentController extends AbstractFormController
{
    public function setAction(CompanySegmentActionModel $segmentActionModel, Request $request): JsonResponse
    {
        $requestParameters = $request->request->all();
        $params            = $requestParameters['company_batch'] ?? [];
        $companyIds        = [];

        if (is_string($params['ids'] ?? null) && '' !== $params['ids']) {
            /** @var array<int> $companyIds */
            $companyIds = json_decode($params['ids'], true, 512, JSON_THROW_ON_ERROR);
        }

        if ([] !== $companyIds && is_array($companyIds)) {
            /** @var array<int> $segmentsToAdd */
            $segmentsToAdd    = $params['add'] ?? [];
            /** @var array<int> $segmentsToRemove */
            $segmentsToRemove = $params['remove'] ?? [];

            if (is_array($segmentsToAdd) && [] !== $segmentsToAdd) {
                $segmentActionModel->addCompanies($companyIds, $segmentsToAdd);
            }

            if (is_array($segmentsToRemove) && [] !== $segmentsToRemove) {
                $segmentActionModel->removeCompanies($companyIds, $segmentsToRemove);
            }

            $this->addFlashMessage('mautic.company_segments.batch_companies_affected', [
                '%count%' => count($companyIds),
            ]);
        } else {
            $this->addFlashMessage('mautic.core.error.ids.missing');
        }

        return new JsonResponse([
            'closeModal' => true,
            'flashes'    => $this->getFlashContent(),
        ]);
    }

    public function indexAction(CompanySegmentModel $segmentModel): Response
    {
        $route    = $this->generateUrl('mautic_company_segments_batch_company_set');
        $segments = $segmentModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'cs.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                ],
            ],
            'orderBy' => 'cs.name',
        ]);
        $items = [];

        foreach ($segments as $segment) {
            $items[$segment->getName().' ('.$segment->getId().')'] = $segment->getId();
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        CompanyBatchType::class,
                        [],
                        [
                            'items'  => $items,
                            'action' => $route,
                        ]
                    )->createView(),
                ],
                'contentTemplate' => '@MauticLead/CompanySegment/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'companyBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
