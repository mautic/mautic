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
        $companyName = 'R&D';

        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get(CompanyModel::class);
        $company      = (new Company())->setName($companyName);
        $companyModel->saveEntity($company);

        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);

        $namedContact = (new Lead())
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('john.doe@example.com');
        $leadModel->saveEntity($namedContact);
        $companyModel->addLeadToCompany($company, $namedContact);
        $leadModel->saveEntity($namedContact);

        $namelessContact = (new Lead())
            ->setEmail('test@example.com');
        $leadModel->saveEntity($namelessContact);
        $companyModel->addLeadToCompany($company, $namelessContact);
        $leadModel->saveEntity($namelessContact);

        $namedContactId    = $namedContact->getId();
        $namelessContactId = $namelessContact->getId();
        $companyId         = $company->getId();
        $this->em->clear();

        $listCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce($listCrawler->html(), $listCrawler->filter('#leadTable')->text(), $companyName);

        $gridCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts?view=grid');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce($gridCrawler->html(), $gridCrawler->filter('.contact-cards')->text(), $companyName);

        $detailCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts/view/'.$namedContactId);
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce(
            $detailCrawler->html(),
            $detailCrawler->filter('.page-header-title, .panel-companies')->text(),
            $companyName
        );

        $namelessDetailCrawler = $this->client->request(Request::METHOD_GET, '/s/contacts/view/'.$namelessContactId);
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce(
            $namelessDetailCrawler->html(),
            $namelessDetailCrawler->filter('.page-header-title')->text(),
            $companyName
        );

        $companyListCrawler = $this->client->request(Request::METHOD_GET, '/s/companies');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce($companyListCrawler->html(), $companyListCrawler->filter('#companyTable')->text(), $companyName);

        $companyContactsCrawler = $this->client->request(Request::METHOD_GET, '/s/company/'.$companyId.'/contacts/');
        $this->assertResponseIsSuccessful();
        $this->assertCompanyNameIsDisplayedOnce(
            $companyContactsCrawler->html(),
            $companyContactsCrawler->filter('#leadTable')->text(),
            $companyName
        );
    }

    private function assertCompanyNameIsDisplayedOnce(string $html, string $visibleText, string $companyName): void
    {
        $encodedOnce  = htmlspecialchars($companyName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $encodedTwice = htmlspecialchars($encodedOnce, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertStringContainsString($companyName, $visibleText);
        $this->assertStringNotContainsString($encodedOnce, $visibleText);
        $this->assertStringNotContainsString($encodedTwice, $html);
        $this->assertStringContainsString($encodedOnce, $html);
    }
}
