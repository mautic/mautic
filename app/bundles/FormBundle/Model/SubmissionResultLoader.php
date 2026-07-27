<?php

namespace Mautic\FormBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;

class SubmissionResultLoader implements MauticModelInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param int $id
     */
    public function getSubmissionWithResult($id): ?Submission
    {
        $repository = $this->getRepository();

        return $repository->getEntity($id);
    }

    private function getRepository(): SubmissionRepository
    {
        return $this->entityManager->getRepository(Submission::class);
    }
}
