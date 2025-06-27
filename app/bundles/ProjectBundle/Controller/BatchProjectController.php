<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\ProjectBundle\Form\Type\BatchProjectType;
use Mautic\ProjectBundle\Model\ProjectActionModel;
use Mautic\ProjectBundle\Model\ProjectModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchProjectController extends AbstractFormController
{
    /**
     * Assigns projects to multiple entities defined by entity ID.
     */
    public function execAction(Request $request, ProjectModel $projectModel, ProjectActionModel $projectActionModel): JsonResponse
    {
        $params = $request->get('project_batch');

        // Handle entity IDs - they come as JSON string from the frontend
        $ids = [];
        if (!empty($params['ids'])) {
            $ids = is_string($params['ids']) ? json_decode($params['ids'], true) : $params['ids'];
            $ids = is_array($ids) ? $ids : [];
        }
        if (!empty($ids)) {
            // Handle project arrays - they come as arrays from the form
            $addToProjects      = !empty($params['add_to']) && is_array($params['add_to']) ? $params['add_to'] : [];
            $removeFromProjects = !empty($params['remove_from']) && is_array($params['remove_from']) ? $params['remove_from'] : [];

            // Convert string IDs to integers
            $addToProjects      = array_map('intval', array_filter($addToProjects));
            $removeFromProjects = array_map('intval', array_filter($removeFromProjects));
            $ids                = array_map('intval', array_filter($ids));

            // Get the entity type from the request to know which entity we're working with
            $entityType = $request->get('entityType', 'email'); // default to email for backwards compatibility

            $affected = [];

            // Only process if we have projects to add or remove
            if (!empty($addToProjects) || !empty($removeFromProjects)) {
                $affected = $projectActionModel->modifyProjectsOnEntities($ids, $addToProjects, $removeFromProjects, $entityType);

                $this->addFlashMessage('mautic.project.batch_entities_affected', [
                    '%count%' => count($affected),
                ]);
            } else {
                $this->addFlashMessage('mautic.project.no_changes_selected');
            }
        } else {
            $this->addFlashMessage('mautic.core.error.ids.missing');
        }

        return new JsonResponse([
            'closeModal'  => true,
            'flashes'     => $this->getFlashContent(),
            'affected'    => !empty($affected) ? array_keys($affected) : [],
            'callback'    => 'projectBatchSubmitCallback',
        ]);
    }

    /**
     * View the modal form for adding projects to entities in batches.
     */
    public function indexAction(Request $request): Response
    {
        $entityType = $request->get('entityType', 'email');
        $route      = $this->generateUrl('mautic_project_batch_set', ['entityType' => $entityType]);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        BatchProjectType::class,
                        [],
                        [
                            'action' => $route,
                            'attr'   => [
                                'data-submit-callback' => 'projectBatchSubmit',
                            ],
                        ]
                    )->createView(),
                    'entityType' => $entityType,
                ],
                'contentTemplate' => '@MauticProject/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_project_index',
                    'mauticContent' => 'projectBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
