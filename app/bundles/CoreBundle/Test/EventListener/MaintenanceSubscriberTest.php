<?php

namespace Mautic\CoreBundle\Test\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\EventListener\MaintenanceSubscriber;
use Mautic\UserBundle\Entity\UserTokenRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private MaintenanceSubscriber $subscriber;

    protected function setUp(): void
    {
        $connection          = $this->createMock(Connection::class);
        $userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $translator          = $this->createMock(TranslatorInterface::class);
        $this->subscriber    = new MaintenanceSubscriber($connection, $userTokenRepository, $translator);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', -50]],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testOnDataCleanup(): void
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $dateTime         = new \DateTimeImmutable();
        $format           = 'Y-m-d H:i:s';
        $translatedString = 'nonsense';

        $dateTimeMock = $this->createMock(\DateTime::class);
        $dateTimeMock
            ->expects($this->exactly(2))
            ->method('format')
            ->with($format)
            ->willReturn($dateTime->format($format));

        $event = $this->createMock(MaintenanceEvent::class);
        $event
            ->expects($this->exactly(2))
            ->method('getDate')
            ->willReturn($dateTimeMock);
        $event
            ->expects($this->exactly(3))
            ->method('isDryRun')
            ->willReturn(false);
        $event
            ->expects($this->exactly(3))
            ->method('setStat');

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder
            ->expects($this->exactly(2))
            ->method('lte')
            ->with('log.date_added', ':date');

        $qb = $this->createMock(QueryBuilder::class);
        $qb
            ->method('select')
            ->willReturn($qb);

        $qb
            ->method('from')
            ->willReturn($qb);

        $qb
            ->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturn($qb);
        $qb
            ->expects($this->exactly(2))
            ->method('delete')
            ->willReturn($qb);
        $qb
            ->expects($this->exactly(4))
            ->method('expr')
            ->willReturn($expressionBuilder);
        $qb
            ->expects($this->exactly(4))
            ->method('where')
            ->willReturn($qb);
        $qb
            ->expects($this->exactly(4))
            ->method('executeQuery')
            ->willReturnCallback(function () {
                static $callCount = 0;
                ++$callCount;
                $result = $this->createMock(\Doctrine\DBAL\Result::class);
                $result->method('fetchAllAssociative')->willReturn(match ($callCount) {
                    1       => [['id' => 765]],
                    3       => [['id' => 764]],
                    default => [],
                });

                return $result;
            });
        $qb
            ->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturn(1);

        $qb->method('setMaxResults')->with(10000)->willReturn($qb);
        $qb->method('setFirstResult')->with(0)->willReturn($qb);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $translator          = $this->createMock(TranslatorInterface::class);
        $userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $subscriber          = new MaintenanceSubscriber($connection, $userTokenRepository, $translator);

        $translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->willReturn($translatedString);

        $subscriber->onDataCleanup($event);
    }
}
