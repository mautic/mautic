<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
    #[AsEventListener]
    public function onEmailPreSave(EntityPreSave $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        if (!$this->config->isPublished() || !$this->emailConfig->isDraftEnabled()) {
            return;
        }

        $this->existingHtml = $email->getCustomHtml() ?? '';

        if (($grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $email])) instanceof GrapesJsBuilder) {
            $this->existingMjml = $grapesJsBuilder->getCustomMjml();
        }
    }

    /**
     * Add an entry.
     */
    #[AsEventListener]
    public function onEmailPostSave(EntityPostSave $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $this->grapesJsBuilderModel->addOrEditEntity($email);
    }

    /**
     * Delete an entry.
     */
    #[AsEventListener]
    public function onEmailDelete(EntityPostDelete $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $email]);

        if ($grapesJsBuilder instanceof GrapesJsBuilder) {
            $this->grapesJsBuilderRepository->deleteEntity($grapesJsBuilder);
        }
    }

    #[AsEventListener(event: EmailEditSubmitEvent::class)]
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
