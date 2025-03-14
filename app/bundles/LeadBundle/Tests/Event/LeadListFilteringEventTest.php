<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

class LeadListFilteringEventTest extends MauticMysqlTestCase
{
    public function testGetEntityManager(): void
    {
        // Create a mock for EntityManager
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManager::class);

        // Create a mock for QueryBuilder with minimal required setup
        $queryBuilder = $this->getMockBuilder(\Mautic\LeadBundle\Segment\Query\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Configure QueryBuilder mock to return a table alias
        $queryBuilder->expects($this->any())
            ->method('getTableAlias')
            ->willReturn('leads_alias');

        // Create event instance with the mocked EntityManager
        $event = new \Mautic\LeadBundle\Event\LeadListFilteringEvent(
            ['field' => 'email'],  // details
            1,                     // leadId
            'lead_alias',          // alias
            'and',                 // func
            $queryBuilder,         // queryBuilder
            $entityManager         // entityManager
        );

        // Assert that getEntityManager returns the expected instance
        $this->assertSame($entityManager, $event->getEntityManager());

        // Additionally test the setters and getters related to filtering
        $event->setFilteringStatus(true);
        $this->assertTrue($event->isFilteringDone());

        $event->setSubQuery('SELECT id FROM leads WHERE email = "test@example.com"');
        $this->assertEquals('SELECT id FROM leads WHERE email = "test@example.com"', $event->getSubQuery());

        $newDetails = ['field' => 'phone'];
        $event->setDetails($newDetails);
        $this->assertEquals($newDetails, $event->getDetails());

        $this->assertEquals('leads_alias', $event->getLeadsTableAlias());
    }
}
