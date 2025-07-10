<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    private string $existingMjml = '';
    private string $existingHtml = '';

    public function __construct(
        private Config $config,
        private GrapesJsBuilderModel $grapesJsBuilderModel,
        private EmailModel $emailModel,
        private EmailConfigInterface $emailConfig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_PRE_SAVE       => ['onEmailPreSave', 0],
            EmailEvents::EMAIL_POST_SAVE      => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_POST_DELETE    => ['onEmailDelete', 0],
            EmailEvents::ON_EMAIL_EDIT_SUBMIT => ['manageEmailDraft'],
        ];
    }

    /**
     * Stores the current MJML for use when managing drafts.
     */
    public function onEmailPreSave(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished() || !$this->emailConfig->isDraftEnabled()) {
            return;
        }

        $email = $event->getEmail();

        $this->existingHtml = $email->getCustomHtml() ?? '';

        if ($grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email])) {
            $this->existingMjml = $grapesJsBuilder->getCustomMjml();
        }
    }

    /**
     * Add an entry.
     */
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
    public function onEmailDelete(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $email           = $event->getEmail();
        $grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);

        if ($grapesJsBuilder) {
            $this->grapesJsBuilderModel->getRepository()->deleteEntity($grapesJsBuilder);
        }
    }

    public function manageEmailDraft(Events\EmailEditSubmitEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $email           = $event->getCurrentEmail();
        $grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);

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

        $this->grapesJsBuilderModel->getRepository()->saveEntity($grapesJsBuilder);
        $this->emailModel->getRepository()->saveEntity($email);
    }
}
