<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Model\ProjectModel;
use Mautic\ProjectBundle\Security\Permissions\ProjectPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    public function getLookupChoiceListAction(Request $request, ProjectModel $projectModel): JsonResponse
    {
        $entityType  = $request->query->get('entityType');
        $projectId   = $request->query->getInt('projectId', 0) ?: null;

        if (empty($entityType)) {
            return new JsonResponse([]);
        }

        $searchKey   = $request->query->get('searchKey', '');
        $searchValue = $request->query->get($searchKey, '');
        $filter      = $searchValue ?: $request->query->get('search', '');
        $limit       = (int) $request->query->get('limit', '10');
        $start       = (int) $request->query->get('start', '0');

        $results = $projectModel->getLookupResults($entityType, $filter, $limit, $start, [
            'projectId' => $projectId,
        ]);

        // Format results to match AjaxLookupControllerTrait structure
        $dataArray = [];
        foreach ($results as $value => $text) {
            $dataArray[] = [
                'text'  => $text,
                'value' => $value,
            ];
        }

        return new JsonResponse($dataArray);
    }

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

                $existingProjectIds[] = $this->createProjectIfNotExists(trim($projectName), $projectModel, $projectRepository);
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

    private function createProjectIfNotExists(string $name, ProjectModel $projectModel, ProjectRepository $projectRepository): int
    {
        if ($project = $projectRepository->findOneBy(['name' => $name])) {
            return $project->getId();
        }

        $project = new Project();
        $project->setName($name);
        $projectModel->saveEntity($project);

        return $project->getId();
    }
}
