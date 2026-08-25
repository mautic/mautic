<?php

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;

final readonly class SubmissionResultLoader implements MauticModelInterface
{
    public static function getName(): string
    {
        return 'form.submission_result_loader';
    }

    public function __construct(
        private SubmissionRepository $submissionRepository,
    ) {
    }

    /**
     * @param int $id
     */
    public function getSubmissionWithResult($id): ?Submission
    {
        return $this->submissionRepository->getEntity($id);
    }
}
