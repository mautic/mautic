<?php

namespace MauticPlugin\MauticTagManagerBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Form\Type\BatchTagType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class BatchTagController extends AbstractFormController
{
    private TagRepository $tagRepository;

    #[Required]
    public function autowireBatchTagController(
        TagRepository $tagRepository,
    ): void {
        $this->tagRepository = $tagRepository;
    }

    private const OBJECT_TYPE_COMPANY = 'company';

    private const OBJECT_TYPE_LEAD    = 'lead';

    public function indexAction(Request $request): Response
    {
        $objectType = $this->getObjectType($request);
        $route      = $this->generateUrl('mautic_tagmanager_batch_set_action', ['objectType' => $objectType]);

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
            $this->throwAccessDenied();
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
        $params     = (array) $request->get('batch_tag');
        $objectType = $this->getObjectType($request);
        $ids        = empty($params['ids']) ? [] : (array) json_decode($params['ids']);
        if ([] === $ids) {
            return $this->getBatchModalResponse('mautic.core.error.ids.missing');
        }

        $tagsToAdd    = $params['tags']['add_tags'] ?? [];
        $tagsToRemove = $params['tags']['remove_tags'] ?? [];

        if (empty($tagsToAdd) && empty($tagsToRemove)) {
            return $this->getBatchModalResponse('mautic.core.error.nothing.to.save');
        }

        $this->applyBatchTags($objectType, $ids, $tagsToAdd, $tagsToRemove);

        return $this->getBatchModalResponse();
    }

    /**
     * @param int[] $ids
     * @param int[] $tagsToAdd
     * @param int[] $tagsToRemove
     */
    private function applyBatchTags(string $objectType, array $ids, array $tagsToAdd, array $tagsToRemove): void
    {
        if (self::OBJECT_TYPE_COMPANY === $objectType) {
            if ([] !== $tagsToAdd) {
                $this->tagRepository->addTagsToCompanies($ids, $tagsToAdd);
            }

            if ([] !== $tagsToRemove) {
                $this->tagRepository->removeTagsFromCompanies($ids, $tagsToRemove);
            }

            $this->addFlashMessage('mautic.company.batch_companies_affected', [
                '%count%' => count($ids),
            ]);

            return;
        }

        if ([] !== $tagsToAdd) {
            $this->tagRepository->addTagsToLeads($ids, $tagsToAdd);
        }

        if ([] !== $tagsToRemove) {
            $this->tagRepository->removeTagsFromLeads($ids, $tagsToRemove);
        }

        $this->addFlashMessage('mautic.lead.batch_leads_affected', [
            '%count%' => count($ids),
        ]);
    }

    private function getBatchModalResponse(?string $flashMessage = null): JsonResponse
    {
        if (null !== $flashMessage) {
            $this->addFlashMessage($flashMessage);
        }

        return new JsonResponse([
            'closeModal' => true,
            'flashes'    => $this->getFlashContent(),
        ]);
    }

    private function getObjectType(Request $request): string
    {
        return self::OBJECT_TYPE_COMPANY === $request->query->get('objectType') ? self::OBJECT_TYPE_COMPANY : self::OBJECT_TYPE_LEAD;
    }
}
