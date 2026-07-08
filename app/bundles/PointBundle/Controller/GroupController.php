<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\PointBundle\Helper\PointGroupSearchScopeProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GroupController extends AbstractStandardFormController
{
    /**
     * @var list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>|null
     */
    private ?array $indexSearchScopes = null;

    protected function getTemplateBase(): string
    {
        return '@MauticPoint/Group';
    }

    protected function getModelName(): string
    {
        return 'point.group';
    }

    /**
     * @param int $page
     */
    public function indexAction(Request $request, PointGroupSearchScopeProvider $pointGroupSearchScopeProvider, $page = 1): Response
    {
        $this->indexSearchScopes = $pointGroupSearchScopeProvider->getScopes();

        return parent::indexStandard($request, $page);
    }

    /**
     * @param array<string, mixed> $args
     * @param string               $action
     *
     * @return array<string, mixed>
     */
    protected function getViewArguments(array $args, $action): array
    {
        if ('index' === $action && null !== $this->indexSearchScopes) {
            $args['viewParameters']['searchScopes'] = $this->indexSearchScopes;
            $this->indexSearchScopes                = null;
        }

        return parent::getViewArguments($args, $action);
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
     * Deletes the entity.
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
