<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\Submission;

#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::postRemove)]
final class SubmissionSubscriber
{
    public function __construct(
        private FormRepository $formRepository,
    ) {
    }

    /**
     * Keep the denormalised forms.submission_count in lock-step with the actual
     * number of form_submissions rows. Incrementing here (instead of on the
     * FORM_ON_SUBMIT event) guarantees symmetry with postRemove: a submission
     * that is persisted and then deleted in the same request - e.g. when a form
     * action throws a ValidationException - nets to zero instead of drifting.
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if (!$form = $this->getSubmissionForm($args)) {
            return;
        }

        $this->formRepository->incrementSubmissionCount($form->getId());
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        if (!$form = $this->getSubmissionForm($args)) {
            return;
        }

        $this->formRepository->decrementSubmissionCount($form->getId());
    }

    private function getSubmissionForm(LifecycleEventArgs $args): ?Form
    {
        $entity = $args->getObject();

        if (!$entity instanceof Submission) {
            return null;
        }

        return $entity->getForm();
    }
}
