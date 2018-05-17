<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Entity\ContactLimiterTrait;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;

class ContactLimiterTraitTest extends \PHPUnit_Framework_TestCase
{
    use ContactLimiterTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);

        $expr = new ExpressionBuilder($this->connection);
        $this->connection->method('getExpressionBuilder')
            ->willReturn($expr);

        $platform = $this->createMock(AbstractPlatform::class);
        $this->connection->method('getDatabasePlatform')
            ->willReturn($platform);
    }

    public function testSpecificContactId()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, 1);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE l.lead_id = :contactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['contactId' => 1], $qb->getParameters());
    }

    public function testListOfContacts()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, null, null, null, [1, 2, 3]);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE l.lead_id IN (:contactIds) LIMIT 50', $qb->getSQL());
        $this->assertEquals(['contactIds' => [1, 2, 3]], $qb->getParameters());
    }

    public function testMinContactId()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, null, 4, null);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE l.lead_id >= :minContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['minContactId' => 4], $qb->getParameters());
    }

    public function testBatchMinContactId()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, null, 4, null);
        $contactLimiter->setBatchMinContactId(10);

        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE l.lead_id >= :minContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['minContactId' => 10], $qb->getParameters());
    }

    public function testMaxContactId()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, null, null, 10);

        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE l.lead_id <= :maxContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['maxContactId' => 10], $qb->getParameters());
    }

    public function testMinAndMaxContactId()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, null, 1, 10);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE l.lead_id BETWEEN :minContactId AND :maxContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['minContactId' => 1, 'maxContactId' => 10], $qb->getParameters());
    }

    public function testThreads()
    {
        $qb             = new QueryBuilder($this->connection);
        $contactLimiter = new ContactLimiter(50, null, null, null, [], 1, 5);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);

        $this->assertEquals('SELECT  WHERE MOD((l.lead_id + :threadShift), :maxThreadId) = 0 LIMIT 50', $qb->getSQL());
        $this->assertEquals(['threadShift' => 0, 'maxThreadId' => 5], $qb->getParameters());
    }
}
