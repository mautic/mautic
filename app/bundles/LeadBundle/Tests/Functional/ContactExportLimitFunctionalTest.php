<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContactExportLimitFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['contact_export_limit'] = 2;
        parent::setUp();
    }

    public function testExportLimitExceeded(): void
    {
        // Load test data
        $this->loadFixtures([LoadLeadData::class]);

        // Create additional contacts to exceed the limit
        /** @var LeadModel $contactModel */
        $contactModel = self::getContainer()->get(LeadModel::class);
        for ($i = 0; $i < 3; ++$i) {
            $contact = new Lead();
            $contact->setFirstname("Test{$i}");
            $contact->setLastname("Contact{$i}");
            $contact->setEmail("test{$i}@test.com");
            $contactModel->saveEntity($contact);
        }

        // Request the export
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=csv');
        $clientResponse = $this->client->getResponse();

        // Assert response code is 400 (Bad Request)
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Decode the JSON response
        $responseData = json_decode($clientResponse->getContent(), true);

        // Assert the response structure and content
        $this->assertStringContainsString('Export limit exceeded', (string) $responseData['message']);
        $this->assertStringContainsString(
            '2 contacts',
            // the limit we set
            (string) $responseData['message']
        );
        $this->assertStringContainsString('Export limit exceeded', (string) $responseData['flashes']);
        $this->assertStringContainsString(
            '2 contacts',
            // the limit we set
            (string) $responseData['flashes']
        );
    }
}
