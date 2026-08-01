<?php

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FocusModel $model,
        private readonly FocusRepository $focusRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_POST_DELETE => ['onFormDelete', 0],
        ];
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onFormDelete(Events\FormEvent $event): void
    {
        $form   = $event->getForm();
        $formId = $form->deletedId;
        $foci   = $this->focusRepository->findByForm($formId);

        if (empty($foci)) {
            return;
        }

        // Rebuild each focus
        /** @var \MauticPlugin\MauticFocusBundle\Entity\Focus $focus */
        foreach ($foci as $focus) {
            $focus->setForm(null);
        }

        $this->model->saveEntities($foci);
    }
}
