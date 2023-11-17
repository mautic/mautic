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

        // get last row sql query from audit_log table
        $sql    = 'SELECT * FROM '.MAUTIC_TABLE_PREFIX.'audit_log ORDER BY id DESC LIMIT 1';
        $stmt   = $this->em->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $this->assertEquals('a:2:{s:7:"options";a:4:{s:8:"days-old";i:180;s:9:"lock_mode";s:3:"pid";s:14:"no-interaction";b:1;s:3:"env";s:4:"test";}s:5:"stats";a:1:{s:8:"Visitors";i:1;}}', $result->fetchAssociative()['details']);
    }
}
