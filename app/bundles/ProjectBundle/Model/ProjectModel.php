<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Service\ProjectEntityLoaderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProjectModel extends FormModel implements AjaxLookupModelInterface
{
    public function __construct(
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $logger,
        CoreParametersHelper $coreParametersHelper,
        private ProjectEntityLoaderService $entityLoaderService,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $logger, $coreParametersHelper);
    }

    public function getRepository(): ProjectRepository
    {
        $repository = $this->em->getRepository(Project::class);
        \assert($repository instanceof ProjectRepository);

        return $repository;
    }

    /**
     * {@inheritDoc}
     *
     * @param string               $type
     * @param string               $filter
     * @param int                  $limit
     * @param int                  $start
     * @param array<string, mixed> $options
     *
     * @return array<int|string, string>
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, array $options = []): array
    {
        // Convert filter to string if it's an array (happens when $data is replaced with actual data)
        if (is_array($filter)) {
            $filter = implode('|', $filter);
        }

        // Extract projectId from options if provided
        $projectId = $options['projectId'] ?? null;

        // Results are already in the correct format (id => name)
        return $this->entityLoaderService->getLookupResults($type, (string) $filter, (int) $limit, (int) $start, $projectId);
    }
}
