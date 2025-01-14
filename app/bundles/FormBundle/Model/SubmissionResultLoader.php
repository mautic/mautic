<?php

namespace Mautic\FormBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;

class SubmissionResultLoader
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $id
     *
     * @return Submission|null
     */
    public function getSubmissionWithResult($id)
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
