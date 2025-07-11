<?php

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactExportLimitFunctionalTest extends MauticMysqlTestCase
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
        $contactModel = self::getContainer()->get('mautic.lead.model.lead');
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
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());

        // Decode the JSON response
        $responseData = json_decode($clientResponse->getContent(), true);

        // Assert the response structure and content
        Assert::assertStringContainsString(
            'Export limit exceeded',
            $responseData['message']
        );
        Assert::assertStringContainsString(
            '2 contacts',  // the limit we set
            $responseData['message']
        );
        Assert::assertStringContainsString(
            'Export limit exceeded',
            $responseData['flashes']
        );
        Assert::assertStringContainsString(
            '2 contacts',  // the limit we set
            $responseData['flashes']
        );
    }
}
