<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Request;

final class ContactCompanyHtmlEntityDisplayFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['contact_columns'] = ['name', 'email', 'company', 'id'];

        parent::setUp();
    }

    public function testCompanyNameWithAmpersandIsDecodedOnContactListAndDetail(): void
    {
        $companyName = 'Peculiar & Co';

        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get(CompanyModel::class);
        $company      = (new Company())->setName($companyName);
        $companyModel->saveEntity($company);

        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);

        $namedContact = (new Lead())
            ->setFirstname('Jane')
            ->setLastname('Umeh')
            ->setEmail('jane.amp-16321@example.test');
        $leadModel->saveEntity($namedContact);
        $companyModel->addLeadToCompany($company, $namedContact);
        $leadModel->saveEntity($namedContact);

        $namelessContact = (new Lead())
            ->setEmail('nameless.amp-16321@example.test');
        $leadModel->saveEntity($namelessContact);
        $companyModel->addLeadToCompany($company, $namelessContact);
        $leadModel->saveEntity($namelessContact);

        $namedContactId = $namedContact->getId();
        $this->em->clear();

        $listCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce($listCrawler->html(), $listCrawler->filter('#leadTable')->text(), $companyName);

        $gridCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts?view=grid');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce($gridCrawler->html(), $gridCrawler->filter('.contact-cards')->text(), $companyName);

        $detailCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts/view/'.$namedContactId);
        $this->assertResponseIsSuccessful();
        $companiesText = $detailCrawler->filter('.panel-companies')->text();
        $this->assertStringContainsString($companyName, $companiesText);
        $this->assertStringNotContainsString('Peculiar &amp; Co', $companiesText);
        $this->assertStringNotContainsString('Peculiar &amp;amp; Co', $detailCrawler->html());
        $this->assertStringContainsString('Peculiar &amp; Co', $detailCrawler->html());

        $companyListCrawler = $this->client->request(Request::METHOD_GET, '/s/companies');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce($companyListCrawler->html(), $companyListCrawler->filter('#companyTable')->text(), $companyName);
    }

    private function assertCompanyNameIsDisplayedOnce(string $html, string $visibleText, string $companyName): void
    {
        $this->assertStringContainsString($companyName, $visibleText);
        $this->assertStringNotContainsString('Peculiar &amp; Co', $visibleText);
        $this->assertStringNotContainsString('Peculiar &amp;amp; Co', $html);
        $this->assertStringContainsString('Peculiar &amp; Co', $html);
    }
}
