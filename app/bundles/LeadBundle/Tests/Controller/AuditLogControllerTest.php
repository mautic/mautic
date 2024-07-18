<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;

final class AuditLogControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testBatchExportAction(): void
    {
        $contact = $this->createLead('TestFirstName');
        $this->em->persist($contact);
        $this->em->flush();

        $this->client->request('GET', '/s/contacts/auditlog/batchExport/'.$contact->getId());
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
