<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\DataFixtures\ORM\CampaignData;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
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

    public function testContactsAreAddedToThenRemovedFromCampaignsInBatch()
    {
        $this->loadFixtures([CampaignData::class, LoadLeadData::class]);

        $payload = [
            'lead_batch' => [
                'add' => [1],
                'ids' => json_encode([1, 2, 3]),
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/s/contacts/batchCampaigns', $payload);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->assertSame(
            [
                [
                    'lead_id'          => '1',
                    'manually_added'   => '1',
                    'manually_removed' => '0',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => '2',
                    'manually_added'   => '1',
                    'manually_removed' => '0',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => '3',
                    'manually_added'   => '1',
                    'manually_removed' => '0',
                    'date_last_exited' => null,
                ],
            ],
            $this->getMembersForCampaign(1)
        );

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertTrue(isset($response['closeModal']), 'The response does not contain the `closeModal` param.');
        $this->assertTrue($response['closeModal']);
        $this->assertStringContainsString('3 contacts affected', $response['flashes']);

        $payload = [
            'lead_batch' => [
                'remove' => [1],
                'ids'    => json_encode([1, 2, 3]),
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/s/contacts/batchCampaigns', $payload);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->assertSame(
            [
                [
                    'lead_id'          => '1',
                    'manually_added'   => '0',
                    'manually_removed' => '1',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => '2',
                    'manually_added'   => '0',
                    'manually_removed' => '1',
                    'date_last_exited' => null,
                ],
                [
                    'lead_id'          => '3',
                    'manually_added'   => '0',
                    'manually_removed' => '1',
                    'date_last_exited' => null,
                ],
            ],
            $this->getMembersForCampaign(1)
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
                'lead[email]'     => 'john@doe.com',
                'lead[companies]' => [$company->getId()],
                'lead[points]'    => 20,
            ]
        );

        $this->client->submit($form);

        /** @var AuditLogRepository $auditLogRepository */
        $auditLogRepository = $this->em->getRepository(AuditLog::class);

        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        /** @var AuditLog[] $auditLogs */
        $auditLogs = $auditLogRepository->getAuditLogs($contactRepository->findOneBy(['email' => 'john@doe.com']));

        Assert::assertSame(
            [
                'firstname' => [
                    0 => null,
                    1 => 'John',
                ],
                'lastname' => [
                    0 => null,
                    1 => 'Doe',
                ],
                'email' => [
                    0 => null,
                    1 => 'john@doe.com',
                ],
                'points' => [
                    0 => 0,
                    1 => 20.0,
                ],
                'company' => [
                    0 => '',
                    1 => 'Doe Corp',
                ],
            ],
            $auditLogs[0]['details']['fields']
        );
    }

    /**
     * Only tests if an actual CSV file is returned and if the content size isn't suspiciously small.
     * We do more in-depth tests in \Mautic\CoreBundle\Tests\Unit\Helper\ExportHelperTest.
     */
    public function testCsvIsExportedCorrectly()
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
    public function testExcelIsExportedCorrectly()
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
            ->fetchAll();
    }

    private function getLeadLists(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('ll.id', 'll.name', 'll.category_id')
            ->from('lead_lists', 'll')
            ->execute()
            ->fetchAll();
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
}
