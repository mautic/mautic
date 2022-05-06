<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\ContactExportSchedulerRepository;

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

    public function getRepository(): ContactExportSchedulerRepository
    {
        return $this->em->getRepository(ContactExportScheduler::class);
    }

    public function deleteEntity(ContactExportScheduler $contactExportScheduler): void
    {
        $this->em->remove($contactExportScheduler);
        $this->em->flush();
    }
}
