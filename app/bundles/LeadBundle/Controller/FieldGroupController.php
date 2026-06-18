<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\LeadBundle\Model\FieldGroupModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldGroupController extends AbstractStandardFormController
{
    protected function getTemplateBase(): string
    {
        return '@MauticLead/FieldGroup';
    }

    protected function getModelName(): string
    {
        return 'lead.field_group';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_lead_field_group_action';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_lead_field_group_index';
    }

    protected function getDefaultOrderColumn(): string
    {
        // DQL field name (mapped to the `field_order` column).
        return 'order';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'ASC';
    }

    /** @param int $page */
    public function indexAction(Request $request, $page = 1): Response
    {
        return parent::indexStandard($request, $page);
    }

    /** @return JsonResponse|Response */
    public function newAction(Request $request)
    {
        return parent::newStandard($request);
    }

    /**
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Deletes the entity; aborts if the group still has fields assigned.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        /** @var FieldGroupModel $model */
        $model  = $this->getModel('lead.field_group');
        $entity = $model->getEntity($objectId);

        if (null !== $entity && $entity->getId() && !$model->canDelete($entity)) {
            $this->addFlashMessage('mautic.lead.field_group.error.has_fields', [], 'error');

            $page = $request->getSession()->get('mautic.lead.field_group.page', 1);

            return $this->postActionRedirect([
                'returnUrl'       => $this->generateUrl('mautic_lead_field_group_index', ['page' => $page]),
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => self::class.'::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_lead_field_group_index',
                    'mauticContent' => 'leadfieldgroup',
                ],
            ]);
        }

        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Filters out any selected group that still has fields (mirrors deleteAction's
     * guard) so a bulk delete can never orphan fields, then deletes the rest.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        /** @var FieldGroupModel $model */
        $model     = $this->getModel('lead.field_group');
        $ids       = json_decode($request->query->get('ids', '[]')) ?: [];
        $deletable = array_values(array_filter($ids, function ($id) use ($model): bool {
            $entity = $model->getEntity((int) $id);

            return null === $entity || $model->canDelete($entity);
        }));

        if (count($deletable) !== count($ids)) {
            $this->addFlashMessage('mautic.lead.field_group.error.has_fields', [], 'error');
        }

        $request->query->set('ids', json_encode($deletable));

        return parent::batchDeleteStandard($request);
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    protected function getViewArguments(array $args, mixed $action): array
    {
        if ('index' === $action) {
            /** @var FieldGroupModel $model */
            $model                                 = $this->getModel('lead.field_group');
            $args['viewParameters']['fieldCounts'] = $model->getFieldCountPerGroup();
            $args['viewParameters']['isAdmin']     = $this->security->isAdmin();
        }

        return $args;
    }
}
