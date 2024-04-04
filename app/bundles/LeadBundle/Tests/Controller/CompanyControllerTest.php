<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

class CompanyControllerTest extends MauticMysqlTestCase
{
    private int $company1Id;

    private int $company2Id;

    protected function setUp(): void
    {
        parent::setUp();

        $companiesData = [
          1 => [
            'name'     => 'Amazon',
            'state'    => 'Washington',
            'city'     => 'Seattle',
            'country'  => 'United States',
            'industry' => 'Goods',
          ],
          2 => [
            'name'     => 'Google',
            'state'    => 'Washington',
            'city'     => 'Seattle',
            'country'  => 'United States',
            'industry' => 'Services',
          ],
        ];

        /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
        $model = self::getContainer()->get('mautic.lead.model.company');

        foreach ($companiesData as $i => $companyData) {
            $company    = new Company();
            $company->setIsPublished(true)
              ->setName($companyData['name'])
              ->setState($companyData['state'])
              ->setCity($companyData['city'])
              ->setCountry($companyData['country'])
              ->setIndustry($companyData['industry']);
            $model->saveEntity($company);

            $this->{'company'.$i.'Id'} = $company->getId();
        }
    }

    /**
     * Get company's view page.
     */
    public function testViewActionCompany(): void
    {
        $this->client->request('GET', '/s/companies/view/'.$this->company1Id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::getContainer()->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->company1Id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString($company->getName(), $clientResponseContent, 'The return must contain the name of company');
    }

    /**
     * Get company's edit page.
     */
    public function testEditActionCompany(): void
    {
        $this->client->request('GET', '/s/companies/edit/'.$this->company1Id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::getContainer()->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->company1Id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString('Edit Company '.$company->getName(), $clientResponseContent, 'The return must contain \'Edit Company\' text');
    }

    /* Get company contacts list */
    public function testListCompanyContacts(): void
    {
        /** @var \Mautic\LeadBundle\Model\CompanyModel $companyModel */
        $companyModel = self::getContainer()->get('mautic.lead.model.company');
        $leadModel    = self::getContainer()->get('mautic.lead.model.lead');
        $company1     = $companyModel->getEntity($this->company1Id);

        // Create a lead linked to the first company
        $lead1    = new Lead();
        $lead1
          ->setFirstname('lead')
          ->setLastname('for '.$company1->getName());
        $leadModel->saveEntity($lead1);

        $companyModel->addLeadToCompany($company1, $lead1);

        // Create a lead not linked to a company
        $lead2    = new Lead();
        $lead2
          ->setFirstname('lead')
          ->setLastname('without company');
        $leadModel->saveEntity($lead2);

        // Create a lead not linked to a company, but with `ids` in it's name (see https://github.com/mautic/mautic/issues/12415)
        $lead3    = new Lead();
        $lead3
          ->setFirstname('lead')
          ->setLastname('without company')
          ->setEmail('example@idstart.com');
        $leadModel->saveEntity($lead3);

        $crawler        = $this->client->request('GET', '/s/company/'.$this->company1Id.'/contacts/');
        $leadsTableRows = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(1, $leadsTableRows->count(), $crawler->html());

        $crawler         = $this->client->request('GET', '/s/company/'.$this->company2Id.'/contacts/');
        $leadsTableRows  = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(0, $leadsTableRows->count(), $crawler->html());
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
