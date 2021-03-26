<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\HttpFoundation\Response;

class CompanyControllerTest extends MauticMysqlTestCase
{
    private $id;

    protected function setUp(): void
    {
        parent::setUp();

        $companyData = [
            'name'     => 'Amazon',
            'state'    => 'Washington',
            'city'     => 'Seattle',
            'country'  => 'United States',
            'industry' => 'Goods',
        ];

        /** @var CompanyModel $model */
        $model      = self::$container->get('mautic.lead.model.company');
        $company    = new Company();
        $company->setIsPublished(true)
            ->setName($companyData['name'])
            ->setState($companyData['state'])
            ->setCity($companyData['city'])
            ->setCountry($companyData['country'])
            ->setIndustry($companyData['industry']);
        $model->saveEntity($company);
        $this->id = $company->getId();
    }

    /**
     * Get company's view page.
     */
    public function testViewActionCompany(): void
    {
        $this->client->request('GET', '/s/companies/view/'.$this->id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::$container->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString($company->getName(), $clientResponseContent, 'The return must contain the name of company');
    }

    /**
     * Get company's edit page.
     */
    public function testEditActionCompany(): void
    {
        $this->client->request('GET', '/s/companies/edit/'.$this->id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::$container->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString('Edit Company '.$company->getName(), $clientResponseContent, 'The return must contain \'Edit Company\' text');
    }

    /* Get company contacts list */
    public function testListCompanyContacts(): void
    {
        $this->client->request('GET', 's/company/'.$this->id.'/contacts/');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::$container->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * Get company's create page.
     */
    public function testNewActionCompany(): void
    {
        $this->client->request('GET', '/s/companies/new/');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }
}
