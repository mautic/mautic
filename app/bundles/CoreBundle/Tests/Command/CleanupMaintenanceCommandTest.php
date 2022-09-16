<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Exception;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class CleanupMaintenanceCommandTest extends MauticMysqlTestCase
{
    /**
     * @throws Exception
     */
    public function testCleanupMaintenanceCommand(): void
    {
        $lead = new Lead();
        $lead->setLastActive(new DateTime('-100 years'));
        $this->em->persist($lead);
        $this->em->flush();

        $contactId = $lead->getId();

        // Delete unused IP address.
        $this->runcommand('mautic:maintenance:cleanup', ['--days-old=180']);

        self::assertFalse($this->getContainer()->get('mautic.lead.model.lead')->getEntity($contactId));
    }
}
