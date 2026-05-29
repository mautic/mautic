<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Mautic\PageBundle\Event\PagePreviewPdfGenerationEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\PageBundle\Pdf\PagePreviewPdfGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PagePreviewPdfSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PagePreviewPdfGenerator $pdfGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_PREVIEW_GENERATE_PDF => ['onPagePreviewGeneratePdf', -255],
        ];
    }

    public function onPagePreviewGeneratePdf(PagePreviewPdfGenerationEvent $event): void
    {
        if ($event->hasPdfContent()) {
            return;
        }

        $event->setPdfContent($this->pdfGenerator->generate($event->getHtmlContent()));
    }
}
