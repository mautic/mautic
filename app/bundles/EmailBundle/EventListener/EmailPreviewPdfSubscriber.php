<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailPreviewPdfGenerationEvent;
use Mautic\EmailBundle\Pdf\EmailPreviewPdfGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EmailPreviewPdfSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailPreviewPdfGenerator $pdfGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_PREVIEW_GENERATE_PDF => ['onEmailPreviewGeneratePdf', -255],
        ];
    }

    public function onEmailPreviewGeneratePdf(EmailPreviewPdfGenerationEvent $event): void
    {
        if ($event->hasPdfContent()) {
            return;
        }

        $event->setPdfContent($this->pdfGenerator->generate($event->getHtmlContent()));
    }
}
