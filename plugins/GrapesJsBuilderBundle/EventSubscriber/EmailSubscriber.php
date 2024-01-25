<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
        private GrapesJsBuilderModel $grapesJsBuilderModel
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_POST_SAVE   => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_POST_DELETE => ['onEmailDelete', 0],
        ];
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
}
