<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class CleanupMaintenanceCommandTest extends MauticMysqlTestCase
{
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
}
