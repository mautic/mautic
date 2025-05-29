<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Model\ProjectModel;
use Mautic\ProjectBundle\Security\Permissions\ProjectPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController extends CommonAjaxController
{
    public function addProjectsAction(Request $request, ProjectModel $projectModel, ProjectRepository $projectRepository, CorePermissions $corePermissions): JsonResponse
    {
        if (!$corePermissions->isGranted(ProjectPermissions::CAN_ASSOCIATE)) {
            $this->accessDenied();
        }

        $existingProjectIds = json_decode($request->request->get('existingProjectIds'), true);
        $newProjectNames    = json_decode($request->request->get('newProjectNames'), true);

        if ($corePermissions->isGranted(ProjectPermissions::CAN_CREATE)) {
            foreach ($newProjectNames as $projectName) {
                $project = new Project();
                $project->setName($projectName);
                $projectModel->saveEntity($project);
                $existingProjectIds[] = $project->getId();
            }
        }

        // Get an updated list of projects
        $allProjects    = $projectRepository->getSimpleList(null, [], 'name');
        $projectOptions = '';

        foreach ($allProjects as $project) {
            $selected = in_array($project['value'], $existingProjectIds) ? ' selected="selected"' : '';
            $projectOptions .= '<option'.$selected.' value="'.$project['value'].'">'.$project['label'].'</option>';
        }

        return $this->sendJsonResponse(['projects' => $projectOptions]);
    }
}
