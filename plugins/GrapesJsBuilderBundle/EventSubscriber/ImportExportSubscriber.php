<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Takes the MJML of a builder email along with the exported email, so the builder can open the imported email.
 * Without it, the builder reads the email as HTML, which breaks the layout of an MJML email.
 */
final readonly class ImportExportSubscriber implements EventSubscriberInterface
{
    public const KEY = 'grapesjsbuilder';

    public function __construct(
        private GrapesJsBuilderRepository $grapesJsBuilderRepository,
        private EmailRepository $emailRepository,
        private EmailMediaImageHelper $mediaImageHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // After the email itself is exported and imported
            EntityExportEvent::class => ['onEmailExport', -10],
            EntityImportEvent::class => ['onEmailImport', -10],
        ];
    }

    public function onEmailExport(EntityExportEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $event->getEntityId()]);
        $mjml            = $grapesJsBuilder?->getCustomMjml();

        if (!is_string($mjml) || '' === $mjml) {
            return;
        }

        $event->addEntityData(Email::ENTITY_NAME, $event->getEntityId(), [self::KEY => ['custom_mjml' => $mjml]]);
    }

    public function onEmailImport(EntityImportEvent $event): void
    {
        if (Email::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $idMap = $event->getEntityIdMap();

        foreach ($event->getEntityData() as $element) {
            $mjml  = $element[self::KEY]['custom_mjml'] ?? null;
            $newId = $idMap[(int) ($element['id'] ?? 0)] ?? null;
            $email = $newId ? $this->emailRepository->find($newId) : null;

            if (!is_string($mjml) || '' === $mjml || !$email instanceof Email) {
                continue;
            }

            $grapesJsBuilder = $this->grapesJsBuilderRepository->findOneBy(['email' => $email]);
            if (!$grapesJsBuilder) {
                $grapesJsBuilder = new GrapesJsBuilder();
                $grapesJsBuilder->setEmail($email);
            }

            // The images point to the source instance, like in the email HTML
            $grapesJsBuilder->setCustomMjml($this->mediaImageHelper->restoreInHtml($mjml));
            $this->grapesJsBuilderRepository->saveEntity($grapesJsBuilder);
        }
    }
}
