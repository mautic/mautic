<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;

final class CompanyListColumnConfigurationFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['company_columns'] = ['companyname', 'score'];

        parent::setUp();
    }

    public function testCompanyIndexRespectsConfiguredListColumns(): void
    {
        $company = new Company();
        $company->setName('Acme Fixtures Ltd');
        $company->setScore(42);

        $this->em->persist($company);
        $this->em->flush();

        $crawler = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/companies');

        $this->assertResponseIsSuccessful();

        $headerCells = $crawler->filter('table#companyTable thead tr th');
        $this->assertCount(3, $headerCells);

        $dataCells = $crawler->filter('table#companyTable tbody tr:first-child td');
        $this->assertCount(3, $dataCells);

        $bodyText = $crawler->filter('table#companyTable tbody')->text();
        $this->assertStringContainsString('Acme Fixtures Ltd', $bodyText);
        $this->assertStringContainsString('42', $bodyText);
    }
}
