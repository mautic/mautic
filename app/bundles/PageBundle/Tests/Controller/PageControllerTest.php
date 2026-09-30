<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\LeadBundle\Entity\UtmTagRepository;
use Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData;
use Mautic\PageBundle\DataFixtures\ORM\LoadPageData;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PageControllerTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    private string $prefix;

    private int $id;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix = self::getContainer()->getParameter('mautic.db_table_prefix');

        $pageData = [
            'title'    => 'Test Page',
            'template' => 'blank',
        ];

        /** @var PageModel $model */
        $model = self::getContainer()->get(PageModel::class);
        $page  = new Page();
        $page->setTitle($pageData['title'])
            ->setTemplate($pageData['template']);

        $model->saveEntity($page);

        $this->id = $page->getId();
    }

    /**
     * Index should return status code 200.
     */
    public function testIndexAction(): void
    {
        $urlAlias   = 'pages';
        $routeAlias = 'page';
        $column     = 'dateModified';
        $column2    = 'title';
        $tableAlias = 'p.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    public function testLandingPageTracking(): void
    {
        $this->logoutUser();

        $leadsTable     = $this->connection->quoteIdentifier($this->prefix.'leads');
        $eventLogsTable = $this->connection->quoteIdentifier($this->prefix.'lead_event_log');

        $pageEntity = new Page();
        $pageEntity->setIsPublished(true);
        $pageEntity->setDateAdded(new \DateTime());
        $pageEntity->setTitle('Page:Page:LandingPageTracking');
        $pageEntity->setAlias('page-page-landingPageTracking');
        $pageEntity->setTemplate('blank');
        $pageEntity->setCustomHtml('some content');
        $pageEntity->setLanguage('en');

        $this->em->persist($pageEntity);
        $this->em->flush();

        $leadsBeforeTest   = $this->connection->fetchAllAssociative("SELECT id FROM $leadsTable");
        $leadIdsBeforeTest = array_column($leadsBeforeTest, 'id');
        $this->client->request('GET', '/page-page-landingPageTracking');
        $this->assertResponseIsSuccessful();

        $sql = "SELECT id FROM $leadsTable";
        if ([] !== $leadIdsBeforeTest) {
            $sanitizedIds = array_map(intval(...), $leadIdsBeforeTest);
            $sql .= ' WHERE id NOT IN ('.implode(',', $sanitizedIds).');';
        }
        $newLeads = $this->connection->fetchAllAssociative($sql);
        $this->assertCount(1, $newLeads);
        $leadId        = reset($newLeads)['id'];

        // Use single quotes for string values to satisfy PostgreSQL strict typing
        // quoteSingleIdentifier ensures "action" is escaped if it's a reserved word
        $actionCol = $this->connection->quoteIdentifier('action');
        $bundleCol = $this->connection->quoteIdentifier('bundle');
        $objectCol = $this->connection->quoteIdentifier('object');
        $leadIdCol = $this->connection->quoteIdentifier('lead_id');

        $leadEventLogs = $this->connection->fetchAllAssociative("
            SELECT id, $actionCol
            FROM $eventLogsTable
            WHERE $leadIdCol = :leadId
            AND $bundleCol = 'page'
            AND $objectCol = 'page'",
            ['leadId' => $leadId]
        );
        $this->assertCount(1, $leadEventLogs);
        $this->assertSame('created_contact', reset($leadEventLogs)['action']);
    }

    /**
     * Skipped for now.
     */
    public function LandingPageTrackingSecondVisit(): void
    {
        $leadsTable     = $this->connection->quoteIdentifier($this->prefix.'leads');
        $eventLogsTable = $this->connection->quoteIdentifier($this->prefix.'lead_event_log');

        $pageEntity = new Page();
        $pageEntity->setIsPublished(true);
        $pageEntity->setDateAdded(new \DateTime());
        $pageEntity->setTitle('Page:Page:LandingPageTrackingSecondVisit');
        $pageEntity->setAlias('page-page-landingPageTrackingSecondVisit');
        $pageEntity->setTemplate('blank');
        $pageEntity->setLanguage('en');

        $this->em->persist($pageEntity);
        $this->em->flush();

        $leadsBeforeTest   = $this->connection->fetchAllAssociative("SELECT id FROM $leadsTable");
        $leadIdsBeforeTest = array_column($leadsBeforeTest, 'id');
        $this->client->request('GET', '/page-page-landingPageTrackingSecondVisit');
        $this->assertResponseIsSuccessful();
        $sql = "SELECT id FROM $leadsTable";
        if ([] !== $leadIdsBeforeTest) {
            $sanitizedIds = array_map(intval(...), $leadIdsBeforeTest);
            $sql .= ' WHERE id NOT IN ('.implode(',', $sanitizedIds).');';
        }
        $newLeadsAfterFirstVisit = $this->connection->fetchAllAssociative($sql);
        $this->assertCount(1, $newLeadsAfterFirstVisit);
        $leadId                   = reset($newLeadsAfterFirstVisit)['id'];

        // Use single quotes for string values to satisfy PostgreSQL strict typing
        // quoteSingleIdentifier ensures "action" is escaped if it's a reserved word
        $actionCol = $this->connection->quoteIdentifier('action');
        $bundleCol = $this->connection->quoteIdentifier('bundle');
        $objectCol = $this->connection->quoteIdentifier('object');
        $leadIdCol = $this->connection->quoteIdentifier('lead_id');

        $eventLogsAfterFirstVisit = $this->connection->fetchAllAssociative("
          SELECT $leadIdCol, $actionCol
          FROM $eventLogsTable
          WHERE $leadIdCol = :leadId
          AND $bundleCol = 'page' AND $objectCol = 'page';", ['leadId' => $leadId]
        );
        $this->assertCount(1, $eventLogsAfterFirstVisit);
        $this->assertSame('created_contact', reset($eventLogsAfterFirstVisit)['action']);
        $this->client->request('GET', '/page-page-landingPageTrackingSecondVisit');
        $this->assertResponseIsSuccessful();
        $eventLogsAfterSecondVisit = $this->connection->fetchAllAssociative("
          SELECT $leadIdCol, $actionCol
          FROM $eventLogsTable
          WHERE $leadIdCol = :leadId
          AND $bundleCol = 'page' AND $objectCol = 'page';", ['leadId' => $leadId]
        );
        $this->assertCount(1, $eventLogsAfterSecondVisit);
        $this->assertSame(reset($eventLogsAfterFirstVisit)['id'], reset($eventLogsAfterSecondVisit)['id']);
    }

    /**
     * Test tracking of a first visit with UTM Tags.
     */
    public function testLandingPageWithUtmTracking(): void
    {
        $this->logoutUser();

        $timestamp  = \time();
        $page       = $this->createTestPage();

        $this->client->request('GET', "/{$page->getAlias()}?utm_source=linkedin&utm_medium=social&utm_campaign=mautic&utm_content=".$timestamp);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $allUtmTags = self::getContainer()->get(UtmTagRepository::class)->getEntities();
        $this->assertNotCount(0, $allUtmTags);

        foreach ($allUtmTags as $utmTag) {
            $this->assertSame('linkedin', $utmTag->getUtmSource(), 'utm_source does not match');
            $this->assertSame('social', $utmTag->getUtmMedium(), 'utm_medium does not match');
            $this->assertSame('mautic', $utmTag->getUtmCampaign(), 'utm_campaign does not match');
            $this->assertSame(strval($timestamp), $utmTag->getUtmContent(), 'utm_content does not match');
        }
    }

    /**
     * @param array<string, mixed> $pageParams
     */
    protected function createTestPage(array $pageParams = []): Page
    {
        $page        = new Page();
        $title       = $pageParams['title'] ?? 'Page:Page:LandingPageTracking';
        $alias       = $pageParams['alias'] ?? 'page-page-landingPageTracking';
        $isPublished = $pageParams['isPublished'] ?? true;
        $template    = $pageParams['template'] ?? 'blank';

        $page->setTitle($title);
        $page->setAlias($alias);
        $page->setIsPublished($isPublished);
        $page->setTemplate($template);
        $page->setCustomHtml('some content');

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    /**
     * Get page's view.
     */
    public function testViewActionPage(): void
    {
        $this->client->request('GET', '/s/pages/view/'.$this->id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        /** @var PageModel $model */
        $model                  = self::getContainer()->get(PageModel::class);
        $page                   = $model->getEntity($this->id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertInstanceOf(Page::class, $page);
        $this->assertStringContainsString($page->getTitle(), (string) $clientResponseContent, 'The return must contain the title of page');
    }

    /**
     * Get landing page's create page.
     */
    public function testNewActionPage(): void
    {
        $this->client->request('GET', '/s/pages/new/');
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /* Get landing page's submissions list */
    public function testListLandingPageSubmissions(): void
    {
        $this->client->request('GET', 's/pages/results/'.$this->id);
        $clientResponse         = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * Only tests if an actual CSV file is returned.
     */
    public function testCsvIsExportedCorrectly(): void
    {
        $this->loadFixtures([LoadPageCategoryData::class, LoadPageData::class]);

        $this->client->request(Request::METHOD_GET, '/s/pages/results/'.$this->id.'/export');

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $this->client->getInternalResponse()->getHeader('content-type'));
    }

    /**
     * Only tests if an actual Excel file is returned.
     */
    public function testExcelIsExportedCorrectly(): void
    {
        $this->loadFixtures([LoadPageCategoryData::class, LoadPageData::class]);

        $this->client->request(Request::METHOD_GET, '/s/pages/results/'.$this->id.'/export/xlsx');

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $this->client->getInternalResponse()->getHeader('content-type'));
    }

    /**
     * Only tests if an actual HTML file is returned.
     */
    public function testHTMLIsExportedCorrectly(): void
    {
        $this->loadFixtures([LoadPageCategoryData::class, LoadPageData::class]);

        $this->client->request(Request::METHOD_GET, '/s/pages/results/'.$this->id.'/export/html');

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $this->client->getInternalResponse()->getHeader('content-type'));
    }

    public function testSavePageAliasWithUnderscores(): void
    {
        /** @var PageModel $pageModel */
        $pageModel = self::getContainer()->get(PageModel::class);

        $parentPage = new Page();
        $parentPage->setTitle('This is My Page');
        $parentPage->setAlias('This_Is_My_Page');
        $parentPage->setTemplate('blank');
        $parentPage->setCustomHtml('This is My Page');
        $pageModel->saveEntity($parentPage);

        $this->client->request(Request::METHOD_GET, '/this_is_my_page');
        self::assertResponseIsSuccessful();
    }
}
