<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

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
    }
}
