<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
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
            ->from('lead_lists', 'll')
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

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
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
}
