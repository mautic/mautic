<?php

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\PageBundle\Event\PageHitEvent;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class StatSubscriber
{
    public function __construct(
        private FocusModel $model,
        private RequestStack $requestStack,
    ) {
    }

    #[AsEventListener(
        event: PageHitEvent::class,
        priority: 0
    )]
    public function onPageHit(PageHitEvent $event): void
    {
        $hit    = $event->getHit();
        $source = $hit->getSource();

        if ('focus' == $source || 'focus.focus' == $source) {
            $sourceId = $hit->getSourceId();
            $focus    = $this->model->getEntity($sourceId);

            if ($focus instanceof Focus && $focus->isPublished()) {
                $this->model->addStat($focus, Stat::TYPE_CLICK, $hit, $hit->getLead());
            }
        }
    }

    /**
     * Note if this submission is from a focus submit.
     */
    #[AsEventListener(
        event: SubmissionEvent::class,
        priority: 0
    )]
    public function onFormSubmit(SubmissionEvent $event): void
    {
        // Check the request for a focus field
        $mauticform = $this->requestStack->getCurrentRequest()->request->all()['mauticform'] ?? [];
        $id         = $mauticform['focusId'] ?? false;

        if (!empty($id)) {
            $focus = $this->model->getEntity($id);

            if ($focus instanceof Focus && $focus->isPublished()) {
                // Make sure the form is still applicable
                $form = $event->getSubmission()->getForm();
                if ((int) $form->getId() === (int) $focus->getForm()) {
                    $this->model->addStat($focus, Stat::TYPE_FORM, $event->getSubmission(), $event->getLead());
                }
            }
        }
    }
}
