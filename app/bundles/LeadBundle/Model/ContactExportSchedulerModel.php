<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\ContactExportScheduler;

class ContactExportSchedulerModel extends AbstractCommonModel
{
    /**
     * @param array<mixed> $data
     */
    public function saveEntity(array $data): ContactExportScheduler
    {
        $contactExportScheduler = new ContactExportScheduler();
        $contactExportScheduler
            ->setUser($this->userHelper->getUser())
            ->setScheduledDateTime(new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setData($data);

        $this->em->persist($contactExportScheduler);
        $this->em->flush();

        return $contactExportScheduler;
    }
}
