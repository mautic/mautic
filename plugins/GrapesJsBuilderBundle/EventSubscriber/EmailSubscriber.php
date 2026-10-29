<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Event\EmailPostDeleteEvent;
use Mautic\EmailBundle\Event\EmailPostSaveEvent;
use Mautic\EmailBundle\Event\EmailPreSaveEvent;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;

final class EmailSubscriber
{
    private string $existingMjml = '';
    private string $existingHtml = '';
    public function __construct(
        private readonly Config $config,
        private readonly GrapesJsBuilderModel $grapesJsBuilderModel,
        private readonly EmailConfigInterface $emailConfig,
        private readonly GrapesJsBuilderRepository $grapesJsBuilderRepository,
        private readonly EmailRepository $emailRepository,
    ) {
    }
    /**
     * Stores the current MJML for use when managing drafts.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: EmailPreSaveEvent::class,
        priority: 0
    )]
    public function onEmailPreSave(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished() || !$this->emailConfig->isDraftEnabled()) {
            return;
        }

        $email = $event->getEmail();

        $this->existingHtml = $email->getCustomHtml() ?? '';

        if ($grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $email])) {
            $this->existingMjml = $grapesJsBuilder->getCustomMjml();
        }
    }
    /**
     * Add an entry.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: EmailPostSaveEvent::class,
        priority: 0
    )]
    public function onEmailPostSave(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $this->grapesJsBuilderModel->addOrEditEntity($event->getEmail());
    }
    /**
     * Delete an entry.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: EmailPostDeleteEvent::class,
        priority: 0
    )]
    public function onEmailDelete(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $email           = $event->getEmail();
        $grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $email]);

        if ($grapesJsBuilder) {
            $this->grapesJsBuilderRepository->deleteEntity($grapesJsBuilder);
        }
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailEditSubmitEvent::class)]
    public function manageEmailDraft(EmailEditSubmitEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $email           = $event->getCurrentEmail();
        $grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $email]);

        if ($event->isSaveAsDraft()) {
            // Set draft MJML and restore previous version when saving a draft
            $grapesJsBuilder->setDraftCustomMjml($grapesJsBuilder->getCustomMjml());
            $grapesJsBuilder->setCustomMjml($this->existingMjml);

            // reset the html of the parent email as well
            $email->setCustomHtml($this->existingHtml);
        }

        if ($event->isApplyDraft()) {
            // Remove the draft version when applying - the customMjml is already up to date
            $grapesJsBuilder->setDraftCustomMjml(null);
        }

        if ($event->isDiscardDraft() && $email->hasDraft()) {
            $grapesJsBuilder->setDraftCustomMjml(null);
        }

        $this->grapesJsBuilderRepository->saveEntity($grapesJsBuilder);
        $this->emailRepository->saveEntity($email);
    }
}
