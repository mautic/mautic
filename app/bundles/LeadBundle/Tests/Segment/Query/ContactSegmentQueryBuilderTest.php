<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    protected function setUp(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    public function testAddNewContactsRestrictions(): void
    {
        $queryBuilder = new QueryBuilder($this->createConnection());
        $queryBuilder->select('1');
        $queryBuilder->where('NULL');

        $filterQueryBuilder = new ContactSegmentQueryBuilder($this->createMock(EntityManager::class), new RandomParameterName(), new EventDispatcher());

        Assert::assertSame($queryBuilder, $filterQueryBuilder->addNewContactsRestrictions($queryBuilder, 8));
        Assert::assertSame('SELECT 1 WHERE (NULL) AND (l.id NOT IN (SELECT par0.lead_id FROM lead_lists_leads par0 WHERE par0.leadlist_id = 8))', $queryBuilder->getDebugOutput());
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
