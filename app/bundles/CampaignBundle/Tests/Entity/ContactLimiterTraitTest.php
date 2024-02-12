<?php

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CampaignBundle\Entity\ContactLimiterTrait;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;

class ContactLimiterTraitTest extends \PHPUnit\Framework\TestCase
{
    use ContactLimiterTrait;
    use MockedConnectionTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    private \PHPUnit\Framework\MockObject\MockObject $connection;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $this->connection = $this->getMockedConnection();

        $expr = new ExpressionBuilder($this->connection);
        $this->connection->method('getExpressionBuilder')
          ->willReturn($expr);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getExpressionBuilder')
            ->willReturn(new Expr());
    }

    public function testSpecificContactId(): void
    {
        $contactLimiter = new ContactLimiter(50, 1);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE l.lead_id = :contactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['contactId' => 1], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) = :contact', $qb->getDQL());
        $this->assertEquals(1, $qb->getParameter('contact')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testListOfContacts(): void
    {
        $contactLimiter = new ContactLimiter(50, null, null, null, [1, 2, 3]);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE l.lead_id IN (:contactIds) LIMIT 50', $qb->getSQL());
        $this->assertEquals(['contactIds' => [1, 2, 3]], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) IN(:contactIds)', $qb->getDQL());
        $this->assertEquals([1, 2, 3], $qb->getParameter('contactIds')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testMinContactId(): void
    {
        $contactLimiter = new ContactLimiter(50, null, 4, null);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE l.lead_id >= :minContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['minContactId' => 4], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) >= :minContactId', $qb->getDQL());
        $this->assertEquals(4, $qb->getParameter('minContactId')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testBatchMinContactId(): void
    {
        $contactLimiter = new ContactLimiter(50, null, 4, null);

        $qb             = new DbalQueryBuilder($this->connection);
        $contactLimiter->setBatchMinContactId(10);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE l.lead_id >= :minContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['minContactId' => 10], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) >= :minContactId', $qb->getDQL());
        $this->assertEquals(10, $qb->getParameter('minContactId')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testMaxContactId(): void
    {
        $contactLimiter = new ContactLimiter(50, null, null, 10);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE l.lead_id <= :maxContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['maxContactId' => 10], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) <= :maxContactId', $qb->getDQL());
        $this->assertEquals(10, $qb->getParameter('maxContactId')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testMinAndMaxContactId(): void
    {
        $contactLimiter = new ContactLimiter(50, null, 1, 10);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE l.lead_id BETWEEN :minContactId AND :maxContactId LIMIT 50', $qb->getSQL());
        $this->assertEquals(['minContactId' => 1, 'maxContactId' => 10], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) BETWEEN :minContactId AND :maxContactId', $qb->getDQL());
        $this->assertEquals(1, $qb->getParameter('minContactId')->getValue());
        $this->assertEquals(10, $qb->getParameter('maxContactId')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testThreads(): void
    {
        $contactLimiter = new ContactLimiter(50, null, null, null, [], 1, 5);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT  WHERE MOD((l.lead_id + :threadShift), :maxThreads) = 0 LIMIT 50', $qb->getSQL());
        $this->assertEquals(['threadShift' => 0, 'maxThreads' => 5], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter);
        $this->assertEquals('SELECT WHERE MOD((IDENTITY(l.lead) + :threadShift), :maxThreads) = 0', $qb->getDQL());
        $this->assertEquals(0, $qb->getParameter('threadShift')->getValue());
        $this->assertEquals(5, $qb->getParameter('maxThreads')->getValue());
        $this->assertEquals(50, $qb->getMaxResults());
    }

    public function testMaxResultsIgnoredForCountQueries(): void
    {
        $contactLimiter = new ContactLimiter(50, 1);

        $qb             = new DbalQueryBuilder($this->connection);
        $this->updateQueryFromContactLimiter('l', $qb, $contactLimiter, true);
        $this->assertEquals('SELECT  WHERE l.lead_id = :contactId', $qb->getSQL());
        $this->assertEquals(['contactId' => 1], $qb->getParameters());

        $qb = new OrmQueryBuilder($this->entityManager);
        $this->updateOrmQueryFromContactLimiter('l', $qb, $contactLimiter, true);
        $this->assertEquals('SELECT WHERE IDENTITY(l.lead) = :contact', $qb->getDQL());
        $this->assertEquals(1, $qb->getParameter('contact')->getValue());
        $this->assertEquals(null, $qb->getMaxResults());
    }
}
