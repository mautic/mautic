<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionTotalRepository;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PageBundle\Helper\AggregateAnalyticsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Keeps the submission totals that the form's stats are built from. A submission counts once it has gone through,
 * unless aggregate analytics are off or it was sent from the form's own preview page.
 */
final readonly class SubmissionTotalSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SubmissionTotalRepository $submissionTotalRepository,
        private AggregateAnalyticsHelper $aggregateAnalyticsHelper,
        private RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    public function onFormSubmit(SubmissionEvent $event): void
    {
        if (!$this->aggregateAnalyticsHelper->isEnabled()) {
            return;
        }

        $submission = $event->getSubmission();
        $form       = $submission->getForm();

        if ($this->isSentFromThePreviewPage($submission, $form)) {
            return;
        }

        $this->submissionTotalRepository->increment($form->getId(), $submission->getDateSubmitted());
    }

    /**
     * The preview page is the form's own public page, so a submission from there is a test, not a result.
     */
    private function isSentFromThePreviewPage(Submission $submission, Form $form): bool
    {
        $refererPath = parse_url((string) $submission->getReferer(), PHP_URL_PATH);

        if (!is_string($refererPath)) {
            return false;
        }

        $previewPath = $this->router->generate('mautic_form_preview', ['id' => $form->getId()]);

        return rtrim($refererPath, '/') === rtrim($previewPath, '/');
    }
}
