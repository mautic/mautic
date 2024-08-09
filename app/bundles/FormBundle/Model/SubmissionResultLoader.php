<?php

namespace Mautic\FormBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;

class SubmissionResultLoader implements MauticModelInterface
{
    public function __construct(
        private EntityManager $entityManager
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

    /**
     * @return SubmissionRepository
     */
    private function getRepository()
    {
        return $this->entityManager->getRepository(Submission::class);
    }
}
