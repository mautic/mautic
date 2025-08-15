<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Illuminate\Support\Collection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Form\Type\ContactGroupPointsType;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $this->configParams['mailer_from_email']   = 'admin@mautic-community.test';
        $this->configParams['messenger_dsn_email'] = 'testEmailSendToContactSync' === $this->getName() ? 'sync://' : 'in-memory://default';

        parent::setUp();
    }

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

        $form = $crawler->filterXPath('//form[@name="leadlist"]')->form();
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
        $firstLeadListLinkTest = trim($leadListsTableRows->first()->filterXPath('//td[2]//div//a')->text(null, false));
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
     * Only tests if a contact export is scheduled for CSV file.
     */
    public function testCsvIsScheduledForExport(): void
    {
        $this->loadFixtures([LoadLeadData::class]);
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=csv');
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertStringContainsString(
            'Contact export scheduled for CSV file type.',
            $clientResponse->getContent()
        );
        $contactExportScheduler = $this->em->getRepository(ContactExportScheduler::class)->findOneBy([]);
        $data                   = $contactExportScheduler->getData();
        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = static::getContainer()->get('mautic.helper.core_parameters');

        Assert::assertSame(
            [
                'start'  => 0,
                'limit'  => $coreParametersHelper->get('contact_export_batch_size', 1000),
                'filter' => [
                    'string' => '',
                    'force'  => [
                        [
                            'column' => 'l.dateIdentified',
                            'expr'   => 'isNotNull',
                        ],
                    ],
                ],
                'orderBy'        => 'l.last_active',
                'orderByDir'     => 'DESC',
                'withTotalCount' => true,
                'fileType'       => 'csv',
            ],
            $data
        );
    }

    /**
     * Only tests if an actual Excel file is returned and if the content size isn't suspiciously small.
     * We do more in-depth tests in \Mautic\CoreBundle\Tests\Unit\Helper\ExportHelperTest.
     */
    public function testExcelIsExportedCorrectly(): void
    {
        $this->loadFixtures([LoadLeadData::class]);
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=xlsx');
        $this->assertResponseIsSuccessful();
        $content = $this->client->getInternalResponse()->getContent();
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertTrue(strlen($content) > 10000, $content);
    }

    public function testContactsAreAddedAndRemovedFromCompanies(): void
    {
        // Running all these in one test to avoid having to re-load fixtures multiple time
        $this->loadFixtures([LoadLeadData::class, LoadCompanyData::class]);

        $this->client->catchExceptions(false);

        // Delete all company associations for this test because the fixures have mismatching data to start with
        $this->connection->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'companies_leads')
            ->executeStatement();

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
        /** @var ChoiceFormField $companyField */
        $companyField = &$form['lead[companies]'];
        $companyField->setValue([]);
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
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function getLeadLists(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('ll.id', 'll.name', 'll.category_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists', 'll')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @testdox Ensure correct Preferred Timezone placeholder on add/edit contact page
     */
    public function testEnsureCorrectPreferredTimeZonePlaceHolderOnContactPage(): void
    {
        $crawler             = $this->client->request('GET', '/s/contacts/new');
        $elementPlaceholder  = $crawler->filter('#lead_timezone')->filter('select')->attr('data-placeholder');
        $expectedPlaceholder = static::getContainer()->get('translator')->trans('mautic.lead.field.timezone');
        $this->assertEquals($expectedPlaceholder, $elementPlaceholder);

        // Test that a locale option is present correctly.
        $this->assertStringContainsString(
            '<option value="cs_CZ">Czech (Czechia)</option>',
            $this->client->getResponse()->getContent()
        );
    }

    public function testQuickAddAction(): void
    {
        $this->client->request('GET', '/s/contacts/quickAdd');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
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

    public function testEmailSendToContactSync(): void
    {
        $contact     = $this->createContact('contact@an.email');
        $replyTo     = 'reply@mautic-community.test';

        $this->client->request(Request::METHOD_GET, "/s/contacts/email/{$contact->getId()}");

        Assert::assertTrue($this->client->getResponse()->isOk());
        $crawler = new Crawler(json_decode($this->client->getResponse()->getContent(), true)['newContent'], $this->client->getInternalRequest()->getUri());
        $form    = $crawler->selectButton('Send')->form();
        $form->setValues(
            [
                'lead_quickemail[subject]'        => 'Ahoy {contactfield=email}',
                'lead_quickemail[body]'           => 'Your email is <b>{contactfield=email}</b>',
                'lead_quickemail[replyToAddress]' => $replyTo,
            ]
        );
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertQueuedEmailCount(1);

        $email      = $this->getMailerMessage();
        $userHelper = static::getContainer()->get(UserHelper::class);
        $user       = $userHelper->getUser();

        Assert::assertSame('Ahoy contact@an.email', $email->getSubject());
        Assert::assertMatchesRegularExpression('#Your email is <b>contact@an\.email<\/b><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $email->getHtmlBody());
        Assert::assertSame('Your email is contact@an.email', $email->getTextBody());
        Assert::assertCount(1, $email->getFrom());
        Assert::assertSame($user->getName(), $email->getFrom()[0]->getName());
        Assert::assertSame($user->getEmail(), $email->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email->getTo());
        Assert::assertSame('', $email->getTo()[0]->getName());
        Assert::assertSame($contact->getEmail(), $email->getTo()[0]->getAddress());
        Assert::assertCount(1, $email->getReplyTo());
        Assert::assertSame('', $email->getReplyTo()[0]->getName());
        Assert::assertSame($replyTo, $email->getReplyTo()[0]->getAddress());
    }

    public function testEmailSendToContactAsync(): void
    {
        // This test should behave the same as sending it via Sync. Just with different settings. See setUp().
        $this->testEmailSendToContactSync();
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

    public function testQuickAddRendersErrorOnEmailDuplicate(): void
    {
        $email = 'duplicate@email.a';
        $this->createContact($email);
        $crawler = $this->client->request('GET', 's/contacts/quickAdd');
        $form    = $crawler->filter('form[name="lead"]')->form([
            'lead' => [
                'email' => $email,
            ],
        ]);

        $crawler = $this->client->submit($form);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $errorContainer = $crawler->filter('form[name="lead"] .has-error .help-block');
        self::assertCount(1, $errorContainer);
        self::assertSame('This field must be unique.', $errorContainer->text(null, true));
    }

    public function testEditRendersErrorOnEmailDuplicate(): void
    {
        $email = 'duplicate@email.a';
        $this->createContact($email);
        $crawler = $this->client->request('GET', 's/contacts/new');
        $form    = $crawler->filter('form[name="lead"]')->form([
            'lead' => [
                'email' => $email,
            ],
        ]);

        $this->client->submit($form);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $clientResponse = $this->client->getResponse();
        Assert::assertStringContainsString('email: This field must be unique.', $clientResponse->getContent());
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
     * @return array<int, array<string, mixed>>
     */
    private function getCompanyLeads(int $leadId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('cl.lead_id, cl.company_id, cl.is_primary, c.companyname')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'companies', 'c', 'c.id = cl.company_id')
            ->where("cl.lead_id = {$leadId}")
            ->orderBy('cl.company_id')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function getLeadPrimaryCompany(int $leadId): ?string
    {
        return $this->connection->createQueryBuilder()
            ->select('l.company')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where("l.id = {$leadId}")
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param int[] $expectedCompanies
     */
    private function assertCompanyAssociation(array $expectedCompanies, int $leadId): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/1');
        $saveButton = $crawler->selectButton('lead[buttons][save]');
        $form       = $saveButton->form();
        /** @var ChoiceFormField $companyField */
        $companyField = &$form['lead[companies]'];
        $companyField->setValue($expectedCompanies);
        $crawler    = $this->client->submit($form);
        $companies  = $this->getCompanyLeads($leadId);
        $collection = (new Collection($companies))->keyBy('company_id');
        // Should have only one company associated
        $this->assertCount(count($expectedCompanies), $collection);
        $this->assertEquals($expectedCompanies, $collection->keys()->toArray());
        // Only one should be primary
        $primary = $collection->reject(
            fn (array $company) => empty($company['is_primary'])
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
        $companyModel = static::getContainer()->get('mautic.lead.model.company');
        /** @var LeadModel $contactModel */
        $contactModel = static::getContainer()->get('mautic.lead.model.lead');

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

    public function testSetNullCompanyToContact(): void
    {
        /** @var LeadModel $contactModel */
        $contactModel = static::getContainer()->get('mautic.lead.model.lead');

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
            ]
        );

        $this->client->submit($form);

        $clientResponse = $this->client->getResponse();

        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        /** @var Lead $contact */
        $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => 'john_23657@doe.com']);

        $companies  = $this->getCompanyLeads($contact->getId());
        $collection = new Collection($companies);
        // Should have no companies associated
        $this->assertCount(1, $collection);

        $this->em->flush();

        $contact->addUpdatedField('company', null);
        $contactModel->saveEntity($contact);

        $companies  = $this->getCompanyLeads($contact->getId());
        $collection = new Collection($companies);
        // Should have no companies associated
        $this->assertCount(0, $collection);
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

    public function testAllAssociatedCompaniesShouldBeFetchedOnContactEditAction(): void
    {
        $contact = $this->createContact('test-contact@a.email');

        // Create more than 100 companies and attached to lead
        $companyLimit = 102;
        $counter      = 1;
        while ($companyLimit >= $counter) {
            $company = new Company();
            $company->setName('TestCompany'.$counter);
            $this->em->persist($company);

            ++$counter;

            $this->createLeadCompany($contact, $company);
        }

        $this->em->flush();

        // verify that all companies are attached to contact
        $companies  = $this->getCompanyLeads($contact->getId());
        Assert::assertCount($companyLimit, $companies);

        $crawler       = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/'.$contact->getId());
        $saveButton    = $crawler->selectButton('lead[buttons][save]');
        $form          = $saveButton->form();
        $leadCompanies = $form['lead[companies]']->getValue();

        Assert::assertCount($companyLimit, $leadCompanies);
    }

    public function testMax100CompaniesShouldBeFetchedOnContactEditAction(): void
    {
        $companyLimit = 123;
        $counter      = 1;
        while ($companyLimit >= $counter) {
            $company = new Company();
            $company->setName('TestCompany'.$counter);
            $this->em->persist($company);
            ++$counter;
        }
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/new');

        // Get the select element for companies
        $companySelect = $crawler->filter('select[name="lead[companies][]"]');

        // Count the number of option elements within the select (- one option that is not a company)
        $availableOptions = $companySelect->filter('option')->count() - 1;

        // Assert that the number of available options is 100 (or your expected limit)
        Assert::assertEquals(100, $availableOptions, 'The number of available company options should be limited to 100');

        // Create a company that will not be visible initially on the list
        $lastCompany = new Company();
        $lastCompany->setName('XYZTestCompany');
        $this->em->persist($lastCompany);
        $this->em->flush();

        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $contact->setEmail('john.doe@example.com');
        $contact->setCompany($lastCompany);
        $this->em->persist($contact);
        $this->em->flush();

        $companyLead = new CompanyLead();
        $companyLead->setLead($contact);
        $companyLead->setCompany($lastCompany);
        $companyLead->setDateAdded(new \DateTime());
        $companyLead->setPrimary(true);
        $this->em->persist($companyLead);
        $this->em->flush();

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/'.$contact->getId());
        $pageContent = $crawler->html();

        // Assure that the last company is present in the contact edit view
        Assert::assertStringContainsString(
            $lastCompany->getName(),
            $pageContent,
            'The hidden company should appear in the contact edit view.'
        );
    }

    public function testNonExitingContactIsRedirected(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(
            Request::METHOD_GET,
            's/contacts/view/1000',
        );
        $this->assertEquals(true, $this->client->getResponse()->isRedirect('/s/contacts/1'));
    }

    public function testContactGroupPointsEdit(): void
    {
        $contact = $this->createContact('test-contact@example.com');

        $groupA = new Group();
        $groupA->setName('Group A');
        $groupB = new Group();
        $groupB->setName('Group B');
        $groupC = new Group();
        $groupC->setName('Group C');
        $this->em->persist($groupA);
        $this->em->persist($groupB);
        $this->em->persist($groupC);
        $this->em->flush();

        $scoresMap = [
            $groupA->getId() => 1,
            $groupB->getId() => 5,
        ];

        $uri = "/s/contacts/contactGroupPoints/{$contact->getId()}";
        $this->client->xmlHttpRequest('GET', $uri);
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();

        // Get the form HTML element out of the response, fill it in and submit.
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="contact_group_points"]')->form();
        $groupAKey    = ContactGroupPointsType::getFieldKey($groupA->getId());
        $groupBKey    = ContactGroupPointsType::getFieldKey($groupB->getId());
        $form->setValues(
            [
                "contact_group_points[{$groupAKey}]" => $scoresMap[$groupA->getId()],
                "contact_group_points[{$groupBKey}]" => $scoresMap[$groupB->getId()],
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();

        $scores = $contact->getGroupScores();
        $this->assertCount(2, $scores);
        foreach ($scores as $score) {
            $this->assertEquals($scoresMap[$score->getGroup()->getId()], $score->getScore());
        }

        $logs = $this->em->getRepository(PointsChangeLog::class)->findBy(['lead' => $contact->getId()]);
        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertEquals($scoresMap[$log->getGroup()->getId()], $log->getDelta());
        }
    }

    public function testMultipleCompanyFeature(): void
    {
        $crawler     = $this->client->request('GET', 's/contacts/new/');
        $multiple    = $crawler->filterXPath('//*[@id="lead_companies"]')->attr('multiple');
        self::assertSame('multiple', $multiple);
    }

    public function testCompanyMergeList(): void
    {
        $companyA = new Company();
        $companyA->setName('Company A');

        $this->em->persist($companyA);

        $companyB = new Company();
        $companyB->setName('Company B');

        $this->em->persist($companyB);

        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/companies/merge/'.$companyA->getId());
        $response = $this->client->getResponse();

        Assert::assertTrue($response->isOk());

        $content = $response->getContent();

        Assert::assertStringContainsString('Company B', $content);
        Assert::assertStringNotContainsString('Company A', $content);
    }

    public function testBatchDncIsNotUpdatingLeadEntities(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $this->em->persist($contact);
        $this->em->flush();
        $this->em->clear();

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/contacts/batchDnc');
        Assert::assertTrue($this->client->getResponse()->isOk());
        $crawler = new Crawler(json_decode($this->client->getResponse()->getContent(), true)['newContent'], $this->client->getInternalRequest()->getUri());
        $form    = $crawler->selectButton('Save')->form();
        $form->setValues(
            [
                'lead_batch_dnc[reason]' => 'Test Reason',
                'lead_batch_dnc[ids]'    => json_encode([$contact->getId()]),
            ]
        );
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $clientResponse = $this->client->getResponse();

        Assert::assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertStringContainsString('1 contact affected', $clientResponse->getContent());

        $dncRepository = $this->em->getRepository(DoNotContact::class);
        \assert($dncRepository instanceof DoNotContactRepository);

        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        $dnc = $dncRepository->findOneBy(['lead' => $contact]);
        \assert($dnc instanceof DoNotContact);

        $fetchedContact = $contactRepository->find($contact->getId());
        \assert($fetchedContact instanceof Lead);

        // Ensure the DNC recored was created.
        Assert::assertSame(DoNotContact::MANUAL, $dnc->getReason());
        Assert::assertSame('Test Reason', $dnc->getComments());
        Assert::assertSame($contact->getId(), $dnc->getLead()->getId());

        // Ensure the dateModified is still empty. Meaning the lead record was not updated which is correct.
        Assert::assertNull($fetchedContact->getDateModified());
    }

    public function testAuditLogBatchExportContact(): void
    {
        $this->loadFixtures([LoadLeadData::class]);

        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=xlsx');
        $content = $this->client->getInternalResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertTrue(strlen($content) > 10000, $content);

        /** @var AuditLog $auditLog */
        $auditLog = $this->em->getRepository(AuditLog::class)->findOneBy([
            'object' => 'ContactExports',
            'bundle' => 'lead',
            'userId' => 1,
            'action' => 'create',
        ]);
        $this->assertNotNull($auditLog);
        Assert::assertTrue(isset($auditLog->getDetails()['args']), json_encode($auditLog, JSON_PRETTY_PRINT));
        Assert::assertSame(
            [
                'start'  => 0,
                'limit'  => 200,
                'filter' => [
                    'string' => '',
                    'force'  => ' !is:anonymous',
                ],
                'orderBy'        => 'l.last_active, l.id',
                'orderByDir'     => 'DESC',
                'withTotalCount' => true,
            ],
            $auditLog->getDetails()['args']
        );
    }

    public function testEmailSendToContactHasCompany(): void
    {
        $company = $this->createCompany('Mautic', 'hello@mautic.org');
        $company->setCity('Pune');
        $company->setCountry('India');
        $this->em->persist($company);

        $contact     = $this->createContact('contact@an.email');
        $this->createPrimaryCompanyForLead($contact, $company);
        $this->em->flush();

        $replyTo     = 'reply@mautic-community.test';

        $this->client->request(Request::METHOD_GET, "/s/contacts/email/{$contact->getId()}");

        $crawler = new Crawler(json_decode($this->client->getResponse()->getContent(), true)['newContent'], $this->client->getInternalRequest()->getUri());
        $form    = $crawler->selectButton('Send')->form();
        $form->setValues(
            [
                'lead_quickemail[subject]'        => 'Ahoy {contactfield=email}',
                'lead_quickemail[body]'           => 'Your email is <b>{contactfield=email}</b>. Company details: {contactfield=companyname}, {contactfield=companycity}.',
                'lead_quickemail[replyToAddress]' => $replyTo,
            ]
        );
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertQueuedEmailCount(1);

        $email      = $this->getMailerMessage();

        Assert::assertSame('Ahoy contact@an.email', $email->getSubject());
        Assert::assertMatchesRegularExpression('#Your email is <b>contact@an\.email<\/b>. Company details: Mautic, Pune.<img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $email->getHtmlBody());
    }
}
