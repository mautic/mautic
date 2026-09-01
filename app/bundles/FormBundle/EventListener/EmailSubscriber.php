<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\EmailBundle\Event\EmailOnBuildEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EmailSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EmailOnBuildEvent::class => ['onEmailBuild', 0],
        ];
    }

    public function onEmailBuild(EmailOnBuildEvent $event): void
    {
        if ($event->abTestWinnerCriteriaRequested()) {
            // add AB Test Winner Criteria
            $formSubmissions = [
                'group'    => 'mautic.form.abtest.criteria',
                'label'    => 'mautic.form.abtest.criteria.submissions',
                'event'    => FormEvents::ON_DETERMINE_SUBMISSION_RATE_WINNER,
            ];
            $event->addAbTestWinnerCriteria('form.submissions', $formSubmissions);
        }
    }
}
