<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Validator\Constraints;

use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueNameValidator extends ConstraintValidator
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {
    }

    public function validate(mixed $project, Constraint $constraint): void
    {
        if (!$project instanceof Project) {
            throw new UnexpectedTypeException($project, Project::class);
        }

        if (!$constraint instanceof UniqueName) {
            throw new UnexpectedTypeException($constraint, UniqueName::class);
        }

        $projectName = $project->getName();

        if (!$projectName) {
            return;
        }

        $ignoredId = $project->isNew() ? null : $project->getId();

        if ($this->projectRepository->checkProjectNameExists($projectName, $ignoredId)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('name')
                ->addViolation();
        }
    }
}
