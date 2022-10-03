<?php

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData;
use Mautic\PageBundle\DataFixtures\ORM\LoadPageData;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageControllerTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $id;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix = self::$container->getParameter('mautic.db_table_prefix');

        $pageData = [
            'title'    => 'Test Page',
            'template' => 'blank',
        ];

        $model = self::$container->get('mautic.page.model.page');
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

    public function testLandingPageTracking()
    {
        $this->connection->insert($this->prefix.'pages', [
            'is_published' => true,
            'date_added'   => (new \DateTime())->format('Y-m-d H:i:s'),
            'title'        => 'Page:Page:LandingPageTracking',
            'alias'        => 'page-page-landingPageTracking',
            'template'     => 'blank',
            'hits'         => 0,
            'unique_hits'  => 0,
            'variant_hits' => 0,
            'revision'     => 0,
            'lang'         => 'en',
        ]);
        $leadsBeforeTest   = $this->connection->fetchAll('SELECT `id` FROM `'.$this->prefix.'leads`;');
        $leadIdsBeforeTest = array_column($leadsBeforeTest, 'id');
        $this->client->request('GET', '/page-page-landingPageTracking');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $sql = 'SELECT `id` FROM `'.$this->prefix.'leads`';
        if (!empty($leadIdsBeforeTest)) {
            $sql .= ' WHERE `id` NOT IN ('.implode(',', $leadIdsBeforeTest).');';
        }
        $newLeads = $this->connection->fetchAll($sql);
        $this->assertCount(1, $newLeads);
        $leadId        = reset($newLeads)['id'];
        $leadEventLogs = $this->connection->fetchAll('
          SELECT `id`, `action`
          FROM `'.$this->prefix.'lead_event_log`
          WHERE `lead_id` = :leadId
          AND `bundle` = "page" AND `object` = "page";', ['leadId' => $leadId]
        );
        $this->assertCount(1, $leadEventLogs);
        $this->assertSame('created_contact', reset($leadEventLogs)['action']);
    }

    /**
     * Skipped for now.
     */
    public function LandingPageTrackingSecondVisit()
    {
        $this->connection->insert($this->prefix.'pages', [
            'is_published' => true,
            'date_added'   => (new \DateTime())->format('Y-m-d H:i:s'),
            'title'        => 'Page:Page:LandingPageTrackingSecondVisit',
            'alias'        => 'page-page-landingPageTrackingSecondVisit',
            'template'     => 'blank',
            'hits'         => 0,
            'unique_hits'  => 0,
            'variant_hits' => 0,
            'revision'     => 0,
            'lang'         => 'en',
        ]);
        $leadsBeforeTest   = $this->connection->fetchAll('SELECT `id` FROM `'.$this->prefix.'leads`;');
        $leadIdsBeforeTest = array_column($leadsBeforeTest, 'id');
        $this->client->request('GET', '/page-page-landingPageTrackingSecondVisit');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $sql = 'SELECT `id` FROM `'.$this->prefix.'leads`';
        if (!empty($leadIdsBeforeTest)) {
            $sql .= ' WHERE `id` NOT IN ('.implode(',', $leadIdsBeforeTest).');';
        }
        $newLeadsAfterFirstVisit = $this->connection->fetchAll($sql);
        $this->assertCount(1, $newLeadsAfterFirstVisit);
        $leadId                   = reset($newLeadsAfterFirstVisit)['id'];
        $eventLogsAfterFirstVisit = $this->connection->fetchAll('
          SELECT `id`, `action`
          FROM `'.$this->prefix.'lead_event_log`
          WHERE `lead_id` = :leadId
          AND `bundle` = "page" AND `object` = "page";', ['leadId' => $leadId]
        );
        $this->assertCount(1, $eventLogsAfterFirstVisit);
        $this->assertSame('created_contact', reset($eventLogsAfterFirstVisit)['action']);
        $this->client->request('GET', '/page-page-landingPageTrackingSecondVisit');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $eventLogsAfterSecondVisit = $this->connection->fetchAll('
          SELECT `id`, `action`
          FROM `'.$this->prefix.'lead_event_log`
          WHERE `lead_id` = :leadId
          AND `bundle` = "page" AND `object` = "page";', ['leadId' => $leadId]
        );
        $this->assertCount(1, $eventLogsAfterSecondVisit);
        $this->assertSame(reset($eventLogsAfterFirstVisit)['id'], reset($eventLogsAfterSecondVisit)['id']);
    }

    /**
     * Test tracking of a first visit with UTM Tags.
     */
    public function testLandingPageWithUtmTracking(): void
    {
        $timestamp  = \time();
        $page       = $this->createTestPage();

        $this->client->request('GET', "{$page->getAlias()}?utm_source=linkedin&utm_medium=social&utm_campaign=mautic&utm_content=".$timestamp);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode(), 'page not found');

        if (Response::HTTP_OK === $clientResponse->getStatusCode()) {
            $allUtmTags    = $this->em->getRepository(UtmTag::class)->getEntities();
            $this->assertNotCount(0, $allUtmTags);

            foreach ($allUtmTags as $utmTag) {
                $this->assertSame('linkedin', $utmTag->getUtmSource(), 'utm_source does not match');
                $this->assertSame('social', $utmTag->getUtmMedium(), 'utm_medium does not match');
                $this->assertSame('mautic', $utmTag->getUtmCampaign(), 'utm_campaign does not match');
                $this->assertSame(strval($timestamp), $utmTag->getUtmContent(), 'utm_content does not match');
            }
        }
    }

    /**
     * Create a page for testing.
     */
    protected function createTestPage($pageParams=[]): Page
    {
        $page = new Page();

        $title       = $pageParams['title'] ?? 'Page:Page:LandingPageTracking';
        $alias       = $pageParams['alias'] ?? 'page-page-landingPageTracking';
        $isPublished = $pageParams['isPublished'] ?? true;
        $template    = $pageParams['template'] ?? 'blank';

        $page->setTitle($title);
        $page->setAlias($alias);
        $page->setIsPublished($isPublished);
        $page->setTemplate($template);

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    /*
     * Get page's view.
     */
    public function testViewActionPage(): void
    {
        $this->client->request('GET', '/s/pages/view/'.$this->id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::$container->get('mautic.page.model.page');
        $page                   = $model->getEntity($this->id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString($page->getTitle(), $clientResponseContent, 'The return must contain the title of page');
    }

    /**
     * Get landing page's create page.
     */
    public function testNewActionPage(): void
    {
        $this->client->request('GET', '/s/pages/new/');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /* Get landing page's submissions list */
    public function testListLandingPageSubmissions(): void
    {
        $this->client->request('GET', 's/pages/results/'.$this->id);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $model                  = self::$container->get('mautic.page.model.page');
        $page                   = $model->getEntity($this->id);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * Only tests if an actual CSV file is returned.
     *
     * @return void
     */
    public function testCsvIsExportedCorrectly()
    {
        $this->loadFixtures([LoadPageCategoryData::class, LoadPageData::class]);

        ob_start();
        $this->client->request(Request::METHOD_GET, '/s/pages/results/'.$this->id.'/export');
        $content = ob_get_contents();
        ob_end_clean();

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'text/csv; charset=UTF-8');
    }

    /**
     * Only tests if an actual Excel file is returned.
     *
     * @return void
     */
    public function testExcelIsExportedCorrectly()
    {
        $this->loadFixtures([LoadPageCategoryData::class, LoadPageData::class]);

        ob_start();
        $this->client->request(Request::METHOD_GET, '/s/pages/results/'.$this->id.'/export/xlsx');
        $content = ob_get_contents();
        ob_end_clean();

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Only tests if an actual HTML file is returned.
     *
     * @return void
     */
    public function testHTMLIsExportedCorrectly()
    {
        $this->loadFixtures([LoadPageCategoryData::class, LoadPageData::class]);

        ob_start();
        $this->client->request(Request::METHOD_GET, '/s/pages/results/'.$this->id.'/export/html');
        $content = ob_get_contents();
        ob_end_clean();

        $clientResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertEquals($this->client->getInternalResponse()->getHeader('content-type'), 'text/html; charset=UTF-8');
    }
}
