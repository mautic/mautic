<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Model;

use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Service\ProjectEntityLoaderService;
use Symfony\Contracts\Service\Attribute\Required;

final class ProjectModel extends FormModel implements AjaxLookupModelInterface
{
    public static function getName(): string
    {
        return 'project.project';
    }

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
}
