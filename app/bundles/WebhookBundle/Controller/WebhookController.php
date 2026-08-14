<?php

namespace Mautic\WebhookBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController extends AbstractStandardFormController
{
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

    /**
     * @param int $page
     */
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
