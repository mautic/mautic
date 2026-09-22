<?php

namespace Mautic\WebhookBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\WebhookBundle\Helper\WebhookSearchScopeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController extends AbstractStandardFormController
{
    /**
     * @var list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>|null
     */
    private ?array $indexSearchScopes = null;

    protected function getModelName(): string
    {
        return 'webhook.webhook';
    }

    protected function getPermissionBase(): string
    {
        return 'webhook:webhooks';
    }

    protected function getRouteBase(): string
    {
        return 'mautic_webhook';
    }

    protected function getSessionBase($objectId = null): string
    {
        return 'mautic.mautic_webhook';
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.webhook';
    }

    protected function getTemplateBase(): string
    {
        return '@MauticWebhook/Webhook';
    }

    protected function getJsLoadMethodPrefix(): string
    {
        return 'mauticWebhook';
    }

    #[Route(
        path: '/s/webhooks/{objectAction}/{objectId}',
        name: 'mautic_webhook_action',
        requirements: ['objectId' => '[a-zA-Z0-9_-]+'],
        defaults: ['objectId' => 0],
    )]
    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = ''): Response
    {
        return parent::executeAction($request, $objectAction, $objectId, $objectSubId, $objectModel);
    }

    /**
     * @param int $page
     */
    #[Route(
        path: '/s/webhooks/{page}',
        name: 'mautic_webhook_index',
        requirements: ['page' => '\d+'],
        defaults: ['page' => 0],
    )]
    public function indexAction(Request $request, WebhookSearchScopeProvider $webhookSearchScopeProvider, $page = 1): Response
    {
        $this->indexSearchScopes = $webhookSearchScopeProvider->getScopes();

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
     * Displays details on a Focus.
     */
    public function viewAction(Request $request, $objectId): Response
    {
        return $this->viewStandard($request, $objectId, 'webhook', 'webhook', null, 'item');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        return parent::cloneStandard($request, $objectId);
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
