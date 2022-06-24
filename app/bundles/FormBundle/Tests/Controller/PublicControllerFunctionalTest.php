<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\HttpFoundation\Request;

final class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    public function testLookupActionWithCompanyData(): void
    {
        $this->createCompany('Company A');
        $this->createCompany('Company B');

        // Payload from the request through the company lookup field.
        $payload = [
            'string' => 'Company',
        ];

        $this->client->request(Request::METHOD_POST, '/form/company-lookup/autocomplete', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        $foundNames = array_column($response, 'value');
        self::assertCount(2, $foundNames);

        foreach ($foundNames as $name) {
            self::assertStringContainsString('Company', $name);
        }
    }
}
