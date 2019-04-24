<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\EventListener\MaintenanceSubscriber;
use Mautic\UserBundle\Entity\UserTokenRepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MaintenanceSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $connection          = $this->createMock(Connection::class);
        $userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $subscriber          = new MaintenanceSubscriber($connection, $userTokenRepository);
        $translator          = $this->createMock(TranslatorInterface::class);
        $subscriber->setTranslator($translator);

        $this->assertEquals(
            [CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', -50]],
            $subscriber::getSubscribedEvents()
        );
    }

    public function testOnDataCleanup()
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', 'mautic');
        }

        $dateTime         = new \DateTimeImmutable();
        $format           = 'Y-m-d H:i:s';
        $rowCount         = 2;
        $translatedString = 'nonsense';

        $dateTimeMock = $this->createMock(\DateTime::class);
        $dateTimeMock->expects($this->exactly(2))
            ->method('format')
            ->with($format)
            ->willReturn($dateTime->format($format));

        $selectExpr = $this->createMock(ExpressionBuilder::class);
        $selectExpr->expects($this->exactly(2))
            ->method('lte')
            ->with('log.date_added', ':date')
            ->willReturn('log.date_added <= :date');

        $statement = $this->createMock(Statement::class);
        $statement->expects($this->exactly(4))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls([['id' => 'x'], ['id' => 'y']], [], [['id' => 'a'], ['id' =>'b']], []);

        $selectQb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'expr', 'setParameter', 'getSQL', 'getParameters'])
            ->getMock();

        $selectQb->expects($this->exactly(2))
            ->method('setParameter')
            ->with('date', $dateTime->format('Y-m-d H:i:s'))
            ->willReturn($selectQb);

        $selectQb->expects($this->exactly(2))
            ->method('expr')
            ->willReturn($selectExpr);

        $selectQb->expects($this->exactly(4))
            ->method('execute')
            ->willReturn($statement);

        $selectQb->expects($this->exactly(2))
            ->method('getSQL')
            ->willReturn('SELECT');

        $selectQb->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(['date' => $dateTime->format('Y-m-d H:i:s')]);

        $deleteExpr = $this->createMock(ExpressionBuilder::class);
        $deleteExpr->expects($this->exactly(2))
            ->method('in')
            ->withConsecutive(
                ['id', ['x', 'y']],
                ['id', ['a', 'b']]
            )
            ->willReturn("id in ('i', 'j')");

        $deleteQb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'expr'])
            ->getMock();

        $deleteQb->expects($this->exactly(2))
            ->method('expr')
            ->willReturn($deleteExpr);

        $deleteQb->expects($this->exactly(2))
            ->method('execute')
            ->willReturn($rowCount);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $selectQb,
                $deleteQb,
                $selectQb,
                $deleteQb
            );

        $userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $userTokenRepository->expects($this->once())
            ->method('deleteExpired')
            ->willReturn(3);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->willReturn($translatedString);

        $subscriber          = new MaintenanceSubscriber($connection, $userTokenRepository);
        $subscriber->setTranslator($translator);

        $event = $this->createMock(MaintenanceEvent::class);
        $event->expects($this->exactly(2))
            ->method('getDate')
            ->willReturn($dateTimeMock);
        $event->expects($this->exactly(3))
            ->method('isDryRun')
            ->willReturn(false);
        $event->expects($this->exactly(3))
            ->method('setStat')
            ->withConsecutive(
                [$translatedString, 2, $this->matches('SELECT'), $this->arrayHasKey('date')],
                [$translatedString, 2, $this->matches('SELECT'), $this->arrayHasKey('date')],
                [$translatedString, 3]
            );

        $this->assertNull($subscriber->onDataCleanup($event));
    }
}
