<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Controller\GraphStatsController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

class GraphStatsControllerTest extends MauticMysqlTestCase
{
    /**
     * @var StatRepository|MockObject
     */
    private $statRepositoryMock;

    /**
     * @var Translator|MockObject
     */
    private $translatorMock;

    /**
     * @var Lead|MockObject
     */
    private $leadMock;

    private GraphStatsController $graphStatsController;

    /**
     * @var LeadModel|MockObject
     */
    private $leadModelMock;

    /**
     * @var CorePermissions|MockObject
     */
    private $securityMock;

    /**
     * @var FlashBag|MockObject
     */
    private $flashBagMock;

    /**
     * @var Environment|MockObject
     */
    private $twig;

    public function setUp(): void
    {
        parent::setUp();
        $this->statRepositoryMock = $this->createMock(StatRepository::class);
        $emailModelMock           = $this->createMock(EmailModel::class);
        $this->leadModelMock      = $this->createMock(LeadModel::class);
        $modelFactoryMock         = $this->createMock(ModelFactory::class);
        $userHelperMock           = $this->createMock(UserHelper::class);
        $this->translatorMock     = $this->createMock(Translator::class);
        $this->securityMock       = $this->createMock(CorePermissions::class);
        $this->leadMock           = $this->createMock(Lead::class);
        $this->flashBagMock       = $this->createMock(FlashBag::class);
        $this->containerMock      = $this->createMock(Container::class);
        $this->twig               = $this->createMock(Environment::class);

        $emailModelMock->method('getStatRepository')
            ->willReturn($this->statRepositoryMock);
        $modelFactoryMock->method('getModel')
            ->willReturnMap([
                ['email', $emailModelMock],
                ['lead.lead', $this->leadModelMock],
            ]);
        $userHelperMock->method('getUser')
            ->willReturn(0);

        $this->graphStatsController = new GraphStatsController(
            $this->createMock(ManagerRegistry::class),
            /** @phpstan-ignore-next-line */
            $this->createMock(MauticFactory::class),
            $modelFactoryMock,
            $userHelperMock,
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->translatorMock,
            $this->flashBagMock,
            $this->createMock(RequestStack::class),
            $this->securityMock
        );

        $this->containerMock->method('has')
            ->with('twig')
            ->willReturn(true);

        $this->containerMock->method('get')
            ->with('twig')
            ->willReturn($this->twig);

        $this->graphStatsController->setContainer($this->containerMock);
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function generateDayStats(): array
    {
        $dayStats = [];
        for ($i = 0; $i < 7; ++$i) {
            $send = rand(0, 10);
            $read = rand(0, $send);
            $hit  = rand(0, $read);

            $dayStats[] = [
                'day'        => $i,
                'sent_count' => (string) $send,
                'read_count' => (string) $read,
                'hit_count'  => (string) $hit,
            ];
        }

        return $dayStats;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function generateHourStats(): array
    {
        $hourStats = [];
        for ($i = 0; $i < 24; ++$i) {
            $send = rand(0, 10);
            $read = rand(0, $send);
            $hit  = rand(0, $read);

            $hourStats[] = [
                'hour'       => $i,
                'sent_count' => (string) $send,
                'read_count' => (string) $read,
                'hit_count'  => (string) $hit,
            ];
        }

        return $hourStats;
    }

    /**
     * @throws Exception
     */
    public function testGetEmailDaysData(): void
    {
        $expectedDayStats = $this->generateDayStats();

        $this->statRepositoryMock->method('getEmailDayStats')
            ->with($this->leadMock)
            ->willReturn($expectedDayStats);

        $this->translatorMock->method('trans')
            ->willReturnOnConsecutiveCalls(
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
                'Email sent',
                'Email read',
                'Email clicked'
            );

        $results = $this->graphStatsController->getEmailDaysData($this->leadMock);

        $this->assertSame(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], $results['labels']);
        $this->assertEquals('Email sent', $results['datasets'][0]['label']);
        $this->assertSame(array_column($expectedDayStats, 'sent_count'), $results['datasets'][0]['data']);
        $this->assertEquals('Email read', $results['datasets'][1]['label']);
        $this->assertSame(array_column($expectedDayStats, 'read_count'), $results['datasets'][1]['data']);
        $this->assertEquals('Email clicked', $results['datasets'][2]['label']);
        $this->assertSame(array_column($expectedDayStats, 'hit_count'), $results['datasets'][2]['data']);
    }

    /**
     * @throws Exception
     */
    public function testGetEmailHoursData(): void
    {
        $expectedHourStats = $this->generateHourStats();

        $this->statRepositoryMock->method('getEmailTimeStats')
            ->with($this->leadMock)
            ->willReturn($expectedHourStats);

        $this->translatorMock->method('trans')
            ->willReturnOnConsecutiveCalls(
                'Email sent',
                'Email read',
                'Email clicked'
            );

        $results = $this->graphStatsController->getEmailHoursData($this->leadMock);

        $this->assertSame([
            '00:00-01:00',
            '01:00-02:00',
            '02:00-03:00',
            '03:00-04:00',
            '04:00-05:00',
            '05:00-06:00',
            '06:00-07:00',
            '07:00-08:00',
            '08:00-09:00',
            '09:00-10:00',
            '10:00-11:00',
            '11:00-12:00',
            '12:00-13:00',
            '13:00-14:00',
            '14:00-15:00',
            '15:00-16:00',
            '16:00-17:00',
            '17:00-18:00',
            '18:00-19:00',
            '19:00-20:00',
            '20:00-21:00',
            '21:00-22:00',
            '22:00-23:00',
            '23:00-00:00',
            ], $results['labels']);
        $this->assertEquals('Email sent', $results['datasets'][0]['label']);
        $this->assertSame(array_column($expectedHourStats, 'sent_count'), $results['datasets'][0]['data']);
        $this->assertEquals('Email read', $results['datasets'][1]['label']);
        $this->assertSame(array_column($expectedHourStats, 'read_count'), $results['datasets'][1]['data']);
        $this->assertEquals('Email clicked', $results['datasets'][2]['label']);
        $this->assertSame(array_column($expectedHourStats, 'hit_count'), $results['datasets'][2]['data']);
    }

    /**
     * @throws Exception
     */
    public function testGetLeadEmailTimeStats(): void
    {
        $expectedDayStats  = $this->generateDayStats();
        $expectedHourStats = $this->generateHourStats();

        $this->statRepositoryMock->method('getEmailDayStats')
            ->with($this->leadMock)
            ->willReturn($expectedDayStats);

        $this->statRepositoryMock->method('getEmailTimeStats')
            ->with($this->leadMock)
            ->willReturn($expectedHourStats);

        $resultsDays        = $this->graphStatsController->getLeadEmailTimeStats($this->leadMock, 'd');
        $resultsHours       = $this->graphStatsController->getLeadEmailTimeStats($this->leadMock, 'h');
        $resultsInvalidUnit = $this->graphStatsController->getLeadEmailTimeStats($this->leadMock, 't');

        $this->assertCount(3, $resultsDays['datasets']);
        $this->assertCount(3, $resultsHours['datasets']);
        $this->assertCount(7, $resultsDays['datasets'][0]['data']);
        $this->assertCount(24, $resultsHours['datasets'][0]['data']);
        $this->assertSame(array_column($expectedDayStats, 'sent_count'), $resultsDays['datasets'][0]['data']);
        $this->assertSame(array_column($expectedHourStats, 'sent_count'), $resultsHours['datasets'][0]['data']);
        $this->assertEmpty($resultsInvalidUnit);
    }

    public function testEmailTimeGraphActionNoPermissions(): void
    {
        $this->leadModelMock->method('getEntity')
            ->willReturn($this->leadMock);

        $userMock = $this->createMock(User::class);
        $this->leadMock->method('getOwner')->willReturn($userMock);

        $this->securityMock->method('hasEntityAccess')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->graphStatsController->emailsTimeGraphAction($this->securityMock, 0, 'd');
    }

    public function testEmailTimeGraphActionFlashMessage(): void
    {
        $this->leadModelMock->method('getEntity')
            ->willReturn($this->leadMock);

        $userMock = $this->createMock(User::class);
        $this->leadMock->method('getOwner')->willReturn($userMock);

        $this->securityMock->method('hasEntityAccess')
            ->willReturn(true);

        $this->statRepositoryMock->method('getEmailTimeStats')
            ->willThrowException(new Exception());

        $this->flashBagMock->expects($this->once())
            ->method('add');

        $this->twig->expects($this->once())
            ->method('render');

        $this->graphStatsController->emailsTimeGraphAction($this->securityMock, 0, 'h');
    }
}
