<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Symfony\Contracts\EventDispatcher\Event;

class ContactExportSchedulerEvent extends Event
{
    private ContactExportScheduler $contactExportScheduler;
    private string $filePath;

    public function __construct(ContactExportScheduler $contactExportScheduler)
    {
        $this->contactExportScheduler = $contactExportScheduler;
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
