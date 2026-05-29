<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class PagePreviewPdfGenerationEvent extends Event
{
    private ?string $pdfContent = null;

    /**
     * @param array<string, mixed>|object|null $contact
     */
    public function __construct(
        private string $htmlContent,
        private Page $page,
        private mixed $contact,
        private Request $request,
        private string $fileName,
    ) {
    }

    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }

    public function setHtmlContent(string $htmlContent): void
    {
        $this->htmlContent = $htmlContent;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    /**
     * @return array<string, mixed>|object|null
     */
    public function getContact(): mixed
    {
        return $this->contact;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function hasPdfContent(): bool
    {
        return null !== $this->pdfContent;
    }

    public function getPdfContent(): string
    {
        return $this->pdfContent ?? '';
    }

    public function setPdfContent(string $pdfContent): void
    {
        $this->pdfContent = $pdfContent;
    }
}
