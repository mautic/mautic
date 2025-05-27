<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;

final class ProjectModel extends FormModel
{
    public function getRepository(): ProjectRepository
    {
        $repository = $this->em->getRepository(Project::class);
        \assert($repository instanceof ProjectRepository);

        return $repository;
    }
}
