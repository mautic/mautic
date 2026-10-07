<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Model;

use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Form\Type\ProjectEntityType;
use Mautic\ProjectBundle\Service\ProjectEntityLoaderService;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\Service\Attribute\Required;

final class ProjectModel extends FormModel implements AjaxLookupModelInterface
{
    private ProjectEntityLoaderService $entityLoaderService;

    private ProjectRepository $projectRepository;

    #[Required]
    public function autowireProjectModel(
        ProjectEntityLoaderService $entityLoaderService,
        ProjectRepository $projectRepository,
    ): void {
        $this->entityLoaderService = $entityLoaderService;
        $this->projectRepository   = $projectRepository;
    }

    public function getRepository(): ProjectRepository
    {
        return $this->projectRepository;
    }

    /**
     * @param string|array<int, string> $filter
     * @param array<string, mixed>      $options
     *
     * @return array<int|string, string>
     */
    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array
    {
        // Convert filter to string if it's an array (happens when $data is replaced with actual data)
        if (is_array($filter)) {
            $filter = implode('|', $filter);
        }

        // Extract projectId from options if provided
        $projectId = $options['projectId'] ?? null;

        // Results are already in the correct format (id => name)
        return $this->entityLoaderService->getLookupResults($type, $filter, $limit, $start, $projectId);
    }

    /**
     * @param string|int|null $id
     */
    public function getEntity($id = null): ?object
    {
        if (null === $id) {
            return new Project();
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Project) {
            throw new MethodNotAllowedHttpException(['Project'], 'Entity must be of class Project()');
        }

        return $formFactory->create(ProjectEntityType::class, $entity, $options);
    }

    public function getPermissionBase(): string
    {
        return 'project:project';
    }
}
