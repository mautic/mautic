<?php

namespace MauticPlugin\MauticTagManagerBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Controller\LeadBatchActionTrait;
use MauticPlugin\MauticTagManagerBundle\Form\Type\BatchTagType;
use MauticPlugin\MauticTagManagerBundle\Model\TagModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchTagController extends AbstractFormController
{
    use LeadBatchActionTrait;

    public function indexAction(): Response
    {
        $route = $this->generateUrl('mautic_tagmanager_batch_set_action');

        $form = $this->createForm(BatchTagType::class, [],
            [
                'action' => $route,
            ]
        )->createView();

        // set some permissions
        $permissions = $this->security->isGranted([
            'tagManager:tagManager:view',
            'tagManager:tagManager:edit',
            'tagManager:tagManager:create',
            'tagManager:tagManager:delete',
        ], 'RETURN_ARRAY');

        if (!$permissions['tagManager:tagManager:view']) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'viewParameters'  => [
                'form' => $form,
            ],
            'contentTemplate' => '@MauticLead/Batch/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_batch_index_action',
                'mauticContent' => 'tagBatch',
                'route'         => $route,
            ],
        ]);
    }

    public function execAction(Request $request): JsonResponse
    {
        $params   = $request->get('batch_tag');
        $tagModel = $this->getModel('tagmanager.tag');
        assert($tagModel instanceof TagModel);
        $ids        = $params['ids'];
        $contactIds = [];

        if ('all' === $ids) {
            $contactIds = $this->getBatchActionEntityIdsForAll($request);
        }

        if (json_decode($ids)) {
            $contactIds = json_decode($ids);
        }

        if (empty($contactIds)) {
            $this->addFlashMessage('mautic.core.error.ids.missing');

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }

        $tagsToAdd    = [];
        $tagsToRemove = [];
        if (!empty($params['tags']['add_tags'])) {
            $tagsToAdd = $params['tags']['add_tags'];
        }
        if (!empty($params['tags']['remove_tags'])) {
            $tagsToRemove = $params['tags']['remove_tags'];
        }
        if (
            empty($tagsToAdd) && empty($tagsToRemove)
        ) {
            $this->addFlashMessage('mautic.core.error.nothing.to.save');

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }

        if (!empty($tagsToAdd)) {
            $tagModel->getRepository()->addTagsToLeads($contactIds, $tagsToAdd);
        }

        if (!empty($tagsToRemove)) {
            $tagModel->getRepository()->removeTagsFromLeads($contactIds, $tagsToRemove);
        }

        $this->addFlashMessage('mautic.lead.batch_leads_affected', [
            '%count%'     => count($contactIds),
        ]);

        return new JsonResponse([
            'closeModal' => true,
            'flashes'    => $this->getFlashContent(),
        ]);
    }
}
