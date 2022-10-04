<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tightenco\Collect\Support\Collection;

class LeadControllerTest extends MauticMysqlTestCase
{
    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createLeadCompany(Lead $contactA, Company $company): CompanyLead
    {
        $leadCompany = new CompanyLead();
        $leadCompany->setLead($contactA);
        $leadCompany->setCompany($company);
        $leadCompany->setDateAdded(new \DateTime());
        $this->em->persist($leadCompany);

        return $leadCompany;
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'companies',
            'campaigns',
            'categories',
            'lead_lists',
        ]);
    }

    /**
     * Assert there is an option to set the new Category type to 'segment'.
     */
    public function testSegmentTypeOptionAvailableOnNewCategoryForm(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/categories/category/new?show_bundle_select=1');
        $clientResponse = $this->client->getResponse();

        $responseContent = json_decode($clientResponse->getContent(), true);
        $contentDom      = new \DOMDocument();
        $contentDom->loadHTML($responseContent['newContent']);

        $xpath = new \DOMXPath($contentDom);

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertEquals(1, $xpath->query("//option[@value='segment']")->count());
    }

    public function testAddCategorizedLeadList(): void
    {
        $this->loadFixtures([LoadCategoryData::class]);
        $crawler        = $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $form    = $crawler->filterXPath('//form[@name="leadlist"]')->form();
        $form->setValues(
            [
                'leadlist[name]'               => 'Segment 1',
                'leadlist[alias]'              => 'segment-1',
                'leadlist[isGlobal]'           => '0',
                'leadlist[isPreferenceCenter]' => '0',
                'leadlist[isPublished]'        => '1',
                'leadlist[publicName]'         => 'Segment 1',
                'leadlist[category]'           => '1',
            ]
        );
        $this->client->submit($form);

        $this->assertEquals(
            [
                [
                    'id'          => '1',
                    'name'        => 'Segment 1',
                    'category_id' => '1',
                ],
            ],
            $this->getLeadLists()
        );
    }

    public function testRetrieveLeadListsBasedOnCategory(): void
    {
        $this->loadFixtures(
            [
                LoadCategoryData::class,
                LoadCategorizedLeadListData::class,
            ]
        );

        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(7, $leadListsTableRows->count());

        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments?filters=["category:1"]');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(4, $leadListsTableRows->count());
        $firstLeadListLinkTest = trim($leadListsTableRows->first()->filterXPath('//td[2]//div//a')->text());
        $this->assertEquals('Lead List 1 - Segment Category 1 (lead-list-1)', $firstLeadListLinkTest);

        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments?filters=["category:2"]');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(2, $leadListsTableRows->count());

        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments?filters=["category:2","category:1"]');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(6, $leadListsTableRows->count());

        $crawler            = $this->client->request(Request::METHOD_GET, '/s/segments?filters=["category:4"]');
        $leadListsTableRows = $crawler->filterXPath("//table[@id='leadListTable']//tbody//tr");
        $this->assertEquals(0, $leadListsTableRows->count());
    }

    public function testContactsAreAddedToThenRemovedFromCampaignsInBatch(): void
    {
        $contactA = $this->createContact('contact@a.email');
        $contactB = $this->createContact('contact@b.email');
        $contactC = $this->createContact('contact@c.email');
        $campaign = $this->createCampaign();
        $payload  = [
            'lead_batch' => [
                'add' => [$campaign->getId()],
                'ids' => json_encode([$contactA->getId(), $contactB->getId(), $contactC->getId()]),
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/s/contacts/batchCampaigns', $payload);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->assertSame(
            [
                [
                    'lead_id'          => (string) $contactA->getId(),
                    'manually_added'   => '1',
                    'manually_removed' => '0',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => (string) $contactB->getId(),
                    'manually_added'   => '1',
                    'manually_removed' => '0',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => (string) $contactC->getId(),
                    'manually_added'   => '1',
                    'manually_removed' => '0',
                    'date_last_exited' => null,
                ],
            ],
            $this->getMembersForCampaign($campaign->getId())
        );

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertTrue(isset($response['closeModal']), 'The response does not contain the `closeModal` param.');
        $this->assertTrue($response['closeModal']);
        $this->assertStringContainsString('3 contacts affected', $response['flashes']);

        $payload = [
            'lead_batch' => [
                'remove' => [$campaign->getId()],
                'ids'    => json_encode([$contactA->getId(), $contactB->getId(), $contactC->getId()]),
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/s/contacts/batchCampaigns', $payload);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->assertSame(
            [
                [
                    'lead_id'          => (string) $contactA->getId(),
                    'manually_added'   => '0',
                    'manually_removed' => '1',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => (string) $contactB->getId(),
                    'manually_added'   => '0',
                    'manually_removed' => '1',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => (string) $contactC->getId(),
                    'manually_added'   => '0',
                    'manually_removed' => '1',
                    'date_last_exited' => null,
                ],
            ],
            $this->getMembersForCampaign($campaign->getId())
        );

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertTrue(isset($response['closeModal']), 'The response does not contain the `closeModal` param.');
        $this->assertTrue($response['closeModal']);
        $this->assertStringContainsString('3 contacts affected', $response['flashes']);
    }

    public function testCompanyChangesAreTrackedWhenContactAddedViaUI(): void
    {
        $company = new Company();
        $company->setName('Doe Corp');

        $this->em->persist($company);
        $this->em->flush();

        $crawler = $this->client->request('GET', 's/contacts/new/');
        $form    = $crawler->filterXPath('//form[@name="lead"]')->form();
        $form->setValues(
            [
                'lead[firstname]' => 'John',
                'lead[lastname]'  => 'Doe',
                'lead[email]'     => 'john_23657@doe.com',
                'lead[companies]' => [$company->getId()],
                'lead[points]'    => 20,
            ]
        );

        $this->client->submit($form);

        $clientResponse = $this->client->getResponse();

        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        /** @var Lead $contact */
        $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => 'john_23657@doe.com']);

        /** @var AuditLog $auditLog */
        $auditLog = $this->em->getRepository(AuditLog::class)->findOneBy(['object' => 'lead', 'objectId' => $contact, 'userId' => 1]);

        Assert::assertTrue(isset($auditLog->getDetails()['fields']), json_encode($auditLog, JSON_PRETTY_PRINT));

        Assert::assertSame(
            [
                'firstname' => [null, 'John'],
                'lastname'  => [null, 'Doe'],
                'email'     => [null, 'john_23657@doe.com'],
                'points'    => [0, 20.0],
                'company'   => ['', 'Doe Corp'],
            ],
            $auditLog->getDetails()['fields']
        );
    }

    /**
     * Only tests if an actual CSV file is returned and if the content size isn't suspiciously small.
     * We do more in-depth tests in \Mautic\CoreBundle\Tests\Unit\Helper\ExportHelperTest.
     */
    public function testCsvIsExportedCorrectly(): void
    {
        $this->loadFixtures([LoadLeadData::class]);

        ob_start();
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=csv');
        $content = ob_get_contents();
        ob_end_clean();

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'text/csv; charset=UTF-8');
        $this->assertEquals(true, (strlen($content) > 5000));
    }

    /**
     * Only tests if an actual Excel file is returned and if the content size isn't suspiciously small.
     * We do more in-depth tests in \Mautic\CoreBundle\Tests\Unit\Helper\ExportHelperTest.
     */
    public function testExcelIsExportedCorrectly(): void
    {
        $this->loadFixtures([LoadLeadData::class]);

        ob_start();
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=xlsx');
        $content = ob_get_contents();
        ob_end_clean();

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertEquals(true, (strlen($content) > 10000));
    }

    public function testContactsAreAddedAndRemovedFromCompanies(): void
    {
        // Running all these in one test to avoid having to re-load fixtures multiple time
        $this->loadFixtures([LoadLeadData::class, LoadCompanyData::class]);

        $this->client->catchExceptions(false);

        // Delete all company associations for this test because the fixures have mismatching data to start with
        $this->connection->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'companies_leads')
            ->execute();

        // Test a single company is added and is set as primary
        $this->assertCompanyAssociation([1], 1);

        // Test that a company a contact is already part of does not change anything
        $this->assertCompanyAssociation([1], 1);

        // Test that multiple companies are added and one primary is set
        $this->assertCompanyAssociation([1, 2, 3], 1);

        // Test that removing a company will leave the two remaining with one set as primary
        $this->assertCompanyAssociation([1, 3], 1);

        // Test that adding a company in addition to others will set it as primary
        $this->assertCompanyAssociation([1, 2, 3], 1);

        // Test that removing all companies will empty the lead's primary company
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/1');
        $saveButton = $crawler->selectButton('lead[buttons][save]');
        $form       = $saveButton->form();
        $form['lead[companies]']->setValue([]);
        $this->client->submit($form);
        $companies  = $this->getCompanyLeads(1);
        $collection = new Collection($companies);
        // Should have no companies associated
        $this->assertCount(0, $collection);
        // Primary company name should match
        $primaryCompanyName = $this->getLeadPrimaryCompany(1);
        $this->assertEmpty($primaryCompanyName);
    }

    private function getMembersForCampaign(int $campaignId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('cl.lead_id, cl.manually_added, cl.manually_removed, cl.date_last_exited')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where("cl.campaign_id = {$campaignId}")
            ->execute()
            ->fetchAllAssociative();
    }

    private function getLeadLists(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('ll.id', 'll.name', 'll.category_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists', 'll')
            ->execute()
            ->fetchAllAssociative();
    }

    /**
     * @testdox Ensure correct Preferred Timezone placeholder on add/edit contact page
     */
    public function testEnsureCorrectPreferredTimeZonePlaceHolderOnContactPage(): void
    {
        $crawler             = $this->client->request('GET', '/s/contacts/new');
        $elementPlaceholder  = $crawler->filter('#lead_timezone')->filter('select')->attr('data-placeholder');
        $expectedPlaceholder = self::$container->get('translator')->trans('mautic.lead.field.timezone');
        $this->assertEquals($expectedPlaceholder, $elementPlaceholder);

        // Test that a locale option is present correctly.
        $this->assertStringContainsString(
            '<option value="cs_CZ" >Czech (Czechia)</option>',
            $this->client->getResponse()->getContent()
        );
    }

    public function testAddContactsErrorMessage(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel     = self::getContainer()->get('mautic.lead.model.field');
        $firstnameField = $fieldModel->getEntity(2);
        $firstnameField->setIsRequired(true);
        $fieldModel->getRepository()->saveEntity($firstnameField);

        $crawler = $this->client->request('GET', 's/contacts/new/');
        $form    = $crawler->filterXPath('//form[@name="lead"]')->form();
        $form->setValues(
            [
            ]
        );

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();

        $this->assertStringContainsString('firstname: This field is required.', $clientResponse->getContent());
    }

    public function testAddContactsErrorMessageForEmailWithTwoDots(): void
    {
        $crawler = $this->client->request('GET', 's/contacts/new/');
        $form    = $crawler->filterXPath('//form[@name="lead"]')->form();
        $form->setValues(
            [
                'lead[email]' => 'john..doe@email.com',
            ]
        );

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();

        $this->assertStringContainsString('email: john..doe@email.com is invalid.', $clientResponse->getContent());
    }

    public function testCompanyIdSearchCommand(): void
    {
        $contactA = $this->createContact('contact@a.email');
        $contactB = $this->createContact('contact@b.email');
        $contactC = $this->createContact('contact@c.email');

        $companyName = 'Doe Corp';
        $company     = new Company();
        $company->setName($companyName);
        $this->em->persist($company);

        $this->em->flush();

        $this->createLeadCompany($contactA, $company);
        $this->createLeadCompany($contactB, $company);

        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=company_id:'.$company->getId());

        $leadsTableRows = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr");
        $this->assertEquals(2, $leadsTableRows->count(), $crawler->html());
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    public function testLookupTypeFieldOnError(): void
    {
        $crawler = $this->client->request('GET', 's/contacts/new/');
        $form    = $crawler->filterXPath('//form[@name="lead"]')->form();
        $form->setValues(
            [
                'lead[title]' => 'Custom title longer like 191 characters Custom title longer like 191 characters Custom title longer like 191 characters Custom title longer like 191 characters Custom title longer like 191 characters Custom title longer like 191 characters ',
            ]
        );

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();
        $this->assertStringContainsString('title: This value is too long. It should have 191 characters or less', $clientResponse->getContent());
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();

        $campaign->setName('Campaign A');
        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => '148',
                        'positionX' => '760',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '860',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => '148',
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @return Lead[]
     */
    private function getCompanyLeads(int $leadId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('cl.lead_id, cl.company_id, cl.is_primary, c.companyname')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'companies', 'c', 'c.id = cl.company_id')
            ->where("cl.lead_id = {$leadId}")
            ->orderBy('cl.company_id')
            ->execute()
            ->fetchAll();
    }

    private function getLeadPrimaryCompany(int $leadId): ?string
    {
        return $this->connection->createQueryBuilder()
            ->select('l.company')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where("l.id = {$leadId}")
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param int[] $expectedCompanies
     */
    private function assertCompanyAssociation(array $expectedCompanies, int $leadId): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/1');
        $saveButton = $crawler->selectButton('lead[buttons][save]');
        $form       = $saveButton->form();
        $form['lead[companies]']->setValue($expectedCompanies);
        $crawler    = $this->client->submit($form);
        $companies  = $this->getCompanyLeads($leadId);
        $collection = (new Collection($companies))->keyBy('company_id');
        // Should have only one company associated
        $this->assertCount(count($expectedCompanies), $collection);
        $this->assertEquals($expectedCompanies, $collection->keys()->toArray());
        // Only one should be primary
        $primary = $collection->reject(
            function (array $company) {
                return empty($company['is_primary']);
            }
        );
        $this->assertCount(1, $primary);
        // Primary company name should match
        $primaryCompanyName = $this->getLeadPrimaryCompany($leadId);
        $this->assertEquals($primary->first()['companyname'], $primaryCompanyName);
        // Primary company should be in the UI of the details dropdown tray
        $details = $crawler->filter('#lead-details')->html();
        $this->assertStringContainsString($primaryCompanyName, $details);
    }

    public function testContactCompanyEditShowsOldCompanyNameInAuditLog(): void
    {
        /** @var CompanyModel $companyModel */
        $companyModel = self::$container->get('mautic.lead.model.company');
        /** @var LeadModel $contactModel */
        $contactModel = self::$container->get('mautic.lead.model.lead');

        // Create companies
        $company = (new Company())
            ->setName('Co.');
        $newCompany = (new Company())
            ->setName('New Co.');
        $companyModel->saveEntities([$company, $newCompany]);
        $companyId    = $company->getId();
        $newCompanyId = $newCompany->getId();

        // Create contact with first 'Co.' company
        $contact = (new Lead())
            ->setFirstname('C1')
            ->setCompany($company);
        $contactModel->saveEntity($contact);

        // Check contact detail view audit log
        $createAuditLog        = $this->getContactAuditLogForSpecificAction($contact, 'create');
        $createAuditLogDetails = $createAuditLog->getDetails();
        // `dateIdentified` is added to the audit log when contact is identified, we want to remove this for easier comparison
        unset($createAuditLogDetails['dateIdentified']);
        Assert::assertSame(
            [
                'firstname' => [null, 'C1'],
                'company'   => [null, $companyId],
            ],
            $createAuditLogDetails
        );

        // Edit contact with second 'New Co.' company
        $contact->setCompany($newCompany);
        $contactModel->saveEntity($contact);

        // Check contact detail view audit log for old value
        $updateAuditLog = $this->getContactAuditLogForSpecificAction($contact, 'update');
        Assert::assertSame(
            [
                'company' => [$companyId, $newCompanyId],
            ],
            $updateAuditLog->getDetails()
        );
    }

    private function getContactAuditLogForSpecificAction(Lead $contact, string $action): AuditLog
    {
        return $this->em->getRepository(AuditLog::class)->findOneBy([
            'bundle'   => 'lead',
            'object'   => 'lead',
            'objectId' => $contact->getId(),
            'action'   => $action,
        ]);
    }
}
