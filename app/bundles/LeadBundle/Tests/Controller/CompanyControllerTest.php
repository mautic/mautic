<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyControllerTest extends MauticMysqlTestCase
{
    private int $company1Id;

    private int $company2Id;

    protected function setUp(): void
    {
        $this->configParams['update_company_mapping_data_in_background'] = false;
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

        /** @var CompanyModel $model */
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
        $crawler                = $this->client->request('GET', '/s/companies/view/'.$this->company1Id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::getContainer()->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->company1Id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString($company->getName(), $clientResponseContent, 'The return must contain the name of company');
        $this->assertSame('', trim($crawler->filter('#company_contact_engagement')->text()));
        $this->assertSame('', trim($crawler->filter('#contacts-table')->text()));
    }

    public function testCompanyViewGraph(): void
    {
        $this->createLead();
        $segment = $this->createSegment();
        $this->testSymfonyCommand('mautic:segments:update', ['--list-id' => $segment->getId()]);
        $crawler  = $this->client->request('GET', "s/company/graph/{$this->company1Id}");
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $body           = json_decode($response->getContent(), true);
        $crawler        = new Crawler($body['newContent']);
        $canvasJson     = trim($crawler->filter('canvas')->html());
        $canvasData     = json_decode($canvasJson, true);
        $datasets       = $canvasData['datasets'] ?? [];
        $engagementData = $datasets[0]['data'] ?? [];
        $totalContacts  = array_sum($engagementData);

        self::assertStringContainsString('Engagements', $response->getContent());
        self::assertSame(1, $totalContacts);
    }

    /**
     * Get company's edit page.
     */
    public function testEditActionCompany(): void
    {
        $crawler                = $this->client->request('GET', '/s/companies/edit/'.$this->company1Id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::getContainer()->get('mautic.lead.model.company');
        $company                = $model->getEntity($this->company1Id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString('Edit Company '.$company->getName(), $clientResponseContent, 'The return must contain \'Edit Company\' text');

        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesRegularExpression('/\/s\/companies\/view\/'.$this->company1Id.'/', $this->client->getRequest()->getUri());
    }

    public function testEditAndCancelActionCompany(): void
    {
        $crawler = $this->client->request('GET', '/s/companies/edit/'.$this->company1Id);
        $this->assertResponseIsSuccessful();
        $buttonCrawler = $crawler->selectButton('Cancel');
        $form          = $buttonCrawler->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesRegularExpression('/\/s\/companies\/view\/'.$this->company1Id.'/', $this->client->getRequest()->getUri());
    }

    /* Get company contacts list */
    public function testListCompanyContacts(): void
    {
        $companyModel = self::getContainer()->get('mautic.lead.model.company');
        \assert($companyModel instanceof CompanyModel);

        $leadModel = self::getContainer()->get('mautic.lead.model.lead');
        \assert($leadModel instanceof LeadModel);

        $company1 = $companyModel->getEntity($this->company1Id);

        // Create a lead linked to the first company
        $lead1 = new Lead();
        $lead1->setFirstname('lead')
            ->setEmail('test1@test.com')
            ->setLastname('for '.$company1->getName());
        $leadModel->saveEntity($lead1);

        $companyModel->addLeadToCompany($company1, $lead1);

        // Create a lead not linked to a company
        $lead2 = new Lead();
        $lead2->setFirstname('lead')
            ->setLastname('without company');
        $leadModel->saveEntity($lead2);

        // Create a lead not linked to a company, but with `ids` in it's name (see https://github.com/mautic/mautic/issues/12415)
        $lead3 = new Lead();
        $lead3->setFirstname('lead')
            ->setLastname('without company')
            ->setEmail('example@idstart.com');
        $leadModel->saveEntity($lead3);

        $crawler        = $this->client->request('GET', '/s/company/'.$this->company1Id.'/contacts/');
        $leadsTableRows = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr");

        $this->assertResponseIsSuccessful();
        $this->assertEquals(1, $leadsTableRows->count(), $crawler->html());

        $clientResponse = $this->client->getResponse();
        $this->assertStringContainsString('test1@test.com', $clientResponse->getContent());
        $this->assertStringContainsString('/s/contacts/view/'.$lead1->getId(), $clientResponse->getContent());
        $this->assertStringContainsString('1 item', $clientResponse->getContent());

        $crawler        = $this->client->request('GET', '/s/company/'.$this->company2Id.'/contacts/');
        $leadsTableRows = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr");

        $this->assertResponseIsSuccessful();
        $this->assertEquals(0, $leadsTableRows->count(), $crawler->html());
    }

    /**
     * Get company's create page.
     */
    public function testNewActionCompany(): void
    {
        $this->client->request('GET', '/s/companies/new/');
        $clientResponse         = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    public function testNonExitingCompanyIsRedirected(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(
            Request::METHOD_GET,
            's/companies/view/1000',
        );
        $this->assertEquals(true, $this->client->getResponse()->isRedirect('/s/companies'));
    }

    public function testNewCompanyMergeButtonVisible(): void
    {
        $this->client->request('GET', '/s/companies/new/');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Use the Crawler to parse the HTML content
        $crawler = new Crawler($clientResponseContent);

        // Check for specific buttons by their IDs
        $applyButton  = $crawler->filter('#company_buttons_apply');
        $saveButton   = $crawler->filter('#company_buttons_save');
        $cancelButton = $crawler->filter('#company_buttons_cancel');
        $mergeButton  = $crawler->filter('#company_buttons_merge');

        $this->assertCount(1, $applyButton, 'Apply button not found');
        $this->assertCount(1, $saveButton, 'Save button not found');
        $this->assertCount(1, $cancelButton, 'Cancel button not found');
        $this->assertCount(0, $mergeButton, 'Merge button found');
    }

    public function testCompanyWithProject(): void
    {
        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/companies/edit/'.$this->company1Id);
        $form    = $crawler->selectButton('Save')->form();
        $form['company[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedCompany = $this->em->find(Company::class, $this->company1Id);
        $this->assertSame($project->getId(), $savedCompany->getProjects()->first()->getId());
    }

    public function testEditActionForChangeInNameReflectsOnLeads(): void
    {
        $leadA = $this->createLead();
        $leadB = $this->createLead('F1', 'L1', 'f@l.com', '123');

        $crawler = $this->client->request('GET', '/s/companies/edit/'.$this->company1Id);
        $this->assertResponseIsSuccessful();

        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();

        $companyModel = self::getContainer()->get('mautic.lead.model.company');
        \assert($companyModel instanceof CompanyModel);

        $company     = $companyModel->getEntity($this->company1Id);
        $updatedName = $company->getName().' - Updated';
        $form->setValues(
            [
                'company[companyname]'    => $updatedName,
                'company[companyemail]'   => $company->getEmail(),
                'company[companystate]'   => $company->getState(),
                'company[companycity]'    => $company->getCity(),
                'company[companycountry]' => $company->getCountry(),
            ]
        );

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesRegularExpression('/\/s\/companies\/view\/'.$this->company1Id.'/', $this->client->getRequest()->getUri());

        /** @var LeadRepository $leadRepo */
        $leadRepo = $this->em->getRepository(Lead::class);
        $this->assertSame($updatedName, $leadRepo->getValue($leadA->getId(), 'company'));
        $this->assertSame($updatedName, $leadRepo->getValue($leadB->getId(), 'company'));
    }

    public function testIndexAction(): void
    {
        $this->client->request('GET', '/s/companies');
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $content = $clientResponse->getContent();

        $companyModel = self::getContainer()->get('mautic.lead.model.company');
        \assert($companyModel instanceof CompanyModel);
        $company1 = $companyModel->getEntity($this->company1Id);
        $company2 = $companyModel->getEntity($this->company2Id);

        $this->assertStringContainsString($company1->getName(), $content);
        $this->assertStringContainsString($company2->getName(), $content);

        $translator  = self::getContainer()->get('translator');
        $itemMessage = $translator->trans('mautic.core.pagination.items', ['%count%' => 2]);
        $this->assertStringContainsString($itemMessage, $content);

        $pageMessage = $translator->trans('mautic.core.pagination.pages', ['%count%' => 1]);
        $this->assertStringContainsString($pageMessage, $content);
    }

    protected function createLead(string $firstName = 'Firstname', string $lastName = 'Lastname', string $email = 'test@test.com', string $phoneNumber = '555-666-777'): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($firstName);
        $lead->setLastname($lastName);
        $lead->setEmail($email);
        $lead->setPhone($phoneNumber);
        $this->em->persist($lead);
        $this->em->flush();

        $companyModel = self::getContainer()->get('mautic.lead.model.company');
        \assert($companyModel instanceof CompanyModel);

        $company = $companyModel->getEntity($this->company1Id);

        $companyModel->addLeadToCompany($company, $lead);

        $lead->setCompany($company->getName());

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createSegment(): LeadList
    {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
        ];

        $segment = new LeadList();
        $segment->setFilters($filters);
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }
}
