<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class CleanupMaintenanceCommandTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @throws \Exception
     */
    public function testCleanupMaintenanceCommand(): void
    {
        $lead = new Lead();
        $lead->setLastActive(new \DateTime('-1 year'));
        $this->em->persist($lead);
        $this->em->flush();

        $contactId = $lead->getId();

        $this->runcommand('mautic:maintenance:cleanup', ['--days-old' => 180, '--no-interaction' => true]);

        $this->assertNull($this->getContainer()->get('mautic.lead.model.lead')->getEntity($contactId));
    }

    /**
     * @throws \Exception
     */
    public function testGdprMaintenanceCommand(): void
    {
        $gdprThreshold = '-4 years';
        $lead          = $this->getLead($gdprThreshold);

        $contactId = $lead->getId();

        $this->testSymfonyCommand('mautic:maintenance:cleanup', ['--gdpr' => 1, '--no-interaction' => true]);

        $this->assertNull($this->getContainer()->get('mautic.lead.model.lead')->getEntity($contactId));

        $this->setUpSymfony($this->configParams + ['gdpr_user_purge_threshold' => '1825']);

        $lead          = $this->getLead($gdprThreshold);
        $contactId     = $lead->getId();

        $this->testSymfonyCommand('mautic:maintenance:cleanup', ['--gdpr' => 1, '--no-interaction' => true]);

        $this->assertNotNull($this->getContainer()->get('mautic.lead.model.lead')->getEntity($contactId));
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getLead(string $gdprThreshold): Lead
    {
        $lead = new Lead();
        $lead->setLastActive(new \DateTime('-1 year'));
        $lead->setDateIdentified(new \DateTime($gdprThreshold));
        $lead->setLastActive(new \DateTime($gdprThreshold));
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }
}
