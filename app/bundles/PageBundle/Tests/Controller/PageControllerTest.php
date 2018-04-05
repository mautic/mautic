<?php

namespace Mautic\PageBundle\Tests\Controller;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

/**
 * Class PageControllerTest.
 */
class PageControllerTest extends MauticMysqlTestCase
{
    /**
     * @var Connection
     */
    private $db;
    /**
     * @var
     */
    private $prefix;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();
        $this->db     = $this->container->get('doctrine.dbal.default_connection');
        $this->prefix = $this->container->getParameter('mautic.db_table_prefix');
        $this->db->beginTransaction();
    }

    /**
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function tearDown()
    {
        $this->db->rollBack();
    }

    public function testLandingPageTracking()
    {
        $this->db->insert($this->prefix.'pages', [
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
        $leadsBeforeTest   = $this->db->fetchAll('SELECT `id` FROM `'.$this->prefix.'leads`;');
        $leadIdsBeforeTest = array_column($leadsBeforeTest, 'id');
        $this->client->request('GET', '/page-page-landingPageTracking');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $newLeads = $this->db->fetchAll('
          SELECT `id`
          FROM `'.$this->prefix.'leads`
          WHERE `id` NOT IN (:leadIds);', ['leadIds' => $leadIdsBeforeTest]);
        $this->assertCount(1, $newLeads);
        $leadId        = reset($newLeads)['id'];
        $leadEventLogs = $this->db->fetchAll('
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
        $this->db->insert($this->prefix.'pages', [
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
        $leadsBeforeTest   = $this->db->fetchAll('SELECT `id` FROM `'.$this->prefix.'leads`;');
        $leadIdsBeforeTest = array_column($leadsBeforeTest, 'id');
        $this->client->request('GET', '/page-page-landingPageTrackingSecondVisit');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $newLeadsAfterFirstVisit = $this->db->fetchAll('
          SELECT `id`
          FROM `'.$this->prefix.'leads`
          WHERE `id` NOT IN (:leadIds);', ['leadIds' => $leadIdsBeforeTest]);
        $this->assertCount(1, $newLeadsAfterFirstVisit);
        $leadId                   = reset($newLeadsAfterFirstVisit)['id'];
        $eventLogsAfterFirstVisit = $this->db->fetchAll('
          SELECT `id`, `action`
          FROM `'.$this->prefix.'lead_event_log`
          WHERE `lead_id` = :leadId
          AND `bundle` = "page" AND `object` = "page";', ['leadId' => $leadId]
        );
        $this->assertCount(1, $eventLogsAfterFirstVisit);
        $this->assertSame('created_contact', reset($eventLogsAfterFirstVisit)['action']);
        $this->client->request('GET', '/page-page-landingPageTrackingSecondVisit');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $eventLogsAfterSecondVisit = $this->db->fetchAll('
          SELECT `id`, `action`
          FROM `'.$this->prefix.'lead_event_log`
          WHERE `lead_id` = :leadId
          AND `bundle` = "page" AND `object` = "page";', ['leadId' => $leadId]
        );
        $this->assertCount(1, $eventLogsAfterSecondVisit);
        $this->assertSame(reset($eventLogsAfterFirstVisit)['id'], reset($eventLogsAfterSecondVisit)['id']);
    }
}
