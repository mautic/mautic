<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;

final readonly class FormSubscriber
{
    public function __construct(
        private FocusModel $model,
        private FocusRepository $focusRepository,
    ) {
    }

    /**
     * Add a delete entry to the audit log.
     */
    #[AsEventListener(event: FormEvents::FORM_POST_DELETE)]
    public function onFormDelete(Events\FormEvent $event): void
    {
        $form   = $event->getForm();
        $formId = $form->deletedId;
        $foci   = $this->focusRepository->findByForm($formId);

        if ($foci === []) {
            return;
        }

        // Rebuild each focus
        /** @var Focus $focus */
        foreach ($foci as $focus) {
            $focus->setForm(null);
        }

        $this->model->saveEntities($foci);
    }
}
