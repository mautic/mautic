<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InsightController extends AbstractStandardFormController
{
    protected function getTemplateBase(): string
    {
        return '@MauticPoint/Insight';
    }

    protected function getModelName(): string
    {
        return 'point.insight';
    }

    #[Route('/s/points/insights/{objectAction}/{objectId}', name: 'mautic_point.insight_action', requirements: ['objectId' => '[a-zA-Z0-9_-]+'], defaults: ['objectId' => 0], priority: -1)]
    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = ''): Response
    {
        return parent::executeAction($request, $objectAction, $objectId, $objectSubId, $objectModel);
    }

    #[Route('/s/points/insights/{page}', name: 'mautic_point.insight_index', requirements: ['page' => '\d+'], defaults: ['page' => 0])]
    public function indexAction(Request $request, $page = 1): Response
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * Generates new form and processes post data.
     */
    public function newAction(Request $request): Response
    {
        return parent::newStandard($request);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     */
    public function editAction(Request $request, $objectId, $ignorePost = false): Response
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Clones an existing Point Insight.
     *
     * @param int $objectId
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes a Point Insight.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): Response
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        return parent::batchDeleteStandard($request);
    }
}
