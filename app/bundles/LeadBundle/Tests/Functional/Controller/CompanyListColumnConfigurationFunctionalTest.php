<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use PHPUnit\Framework\Assert;

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

        $crawler = $this->client->request('GET', '/s/companies');

        $this->assertResponseIsSuccessful();

        $headerCells = $crawler->filter('table#companyTable thead tr th');
        Assert::assertCount(3, $headerCells);

        $dataCells = $crawler->filter('table#companyTable tbody tr:first-child td');
        Assert::assertCount(3, $dataCells);

        $bodyText = $crawler->filter('table#companyTable tbody')->text();
        Assert::assertStringContainsString('Acme Fixtures Ltd', $bodyText);
        Assert::assertStringContainsString('42', $bodyText);
    }
}
