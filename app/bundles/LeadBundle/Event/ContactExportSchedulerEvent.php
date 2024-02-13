<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Symfony\Contracts\EventDispatcher\Event;

class ContactExportSchedulerEvent extends Event
{
    private string $filePath;

    public function __construct(
        private ContactExportScheduler $contactExportScheduler
    ) {
    }

    public function getContactExportScheduler(): ContactExportScheduler
    {
        return $this->contactExportScheduler;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }
}
