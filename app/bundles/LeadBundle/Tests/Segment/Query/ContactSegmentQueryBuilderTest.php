<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ContactSegmentQueryBuilderTest extends TestCase
{
    public function testAddNewContactsRestrictions(): void
    {
        $queryBuilder = new QueryBuilder($this->createConnection());
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');
        $queryBuilder->where('NULL');

        $filterQueryBuilder = new ContactSegmentQueryBuilder($this->createMock(EntityManager::class), new RandomParameterName(), new EventDispatcher());

        Assert::assertSame($queryBuilder, $filterQueryBuilder->addNewContactsRestrictions($queryBuilder, 8));
        Assert::assertSame('SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'leads l WHERE (NULL) AND (l.id NOT IN (SELECT par0.lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads par0 WHERE par0.leadlist_id = 8))', $queryBuilder->getDebugOutput());
    }

    private function createConnection(): Connection
    {
        return new class() extends Connection {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
            }
        };
    }
}
