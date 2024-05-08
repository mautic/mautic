<?php

namespace MauticPlugin\MauticTagManagerBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\MauticTagManagerBundle\Form\Type\BatchTagType;
use MauticPlugin\MauticTagManagerBundle\Model\TagModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchTagController extends AbstractFormController
{
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
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);
        if (empty($ids)) {
            $this->addFlashMessage('mautic.core.error.ids.missing');

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }

        $tagsToAdd    = [];
        $tagsToRemove = [];
        if (isset($params['tags']['add_tags']) && !empty($params['tags']['add_tags'])) {
            $tagsToAdd = $params['tags']['add_tags'];
        }
        if (isset($params['tags']['remove_tags']) && !empty($params['tags']['remove_tags'])) {
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
            $tagModel->getRepository()->addTagsToLeads($ids, $tagsToAdd);
        }

        if (!empty($tagsToRemove)) {
            $tagModel->getRepository()->removeTagsFromLeads($ids, $tagsToRemove);
        }

        $this->addFlashMessage('mautic.lead.batch_leads_affected', [
            '%count%'     => count($ids),
        ]);

        return new JsonResponse([
            'closeModal' => true,
            'flashes'    => $this->getFlashContent(),
        ]);
    }
}
