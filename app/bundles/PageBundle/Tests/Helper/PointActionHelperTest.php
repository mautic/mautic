<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Helper\PointActionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PointActionHelperTest extends TestCase
{
    /**
     * @var MockObject|MauticFactory
     */
    private $factory;

    /**
     * @var MockObject|EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MockObject|HitRepository
     */
    private $hitRepository;

    /**
     * @var MockObject|Lead
     */
    private $lead;

    /**
     * @var MockObject|Hit
     */
    private $eventDetails;

    protected function setUp(): void
    {
        /** @phpstan-ignore-next-line */
        $this->factory       = $this->createMock(MauticFactory::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->hitRepository = $this->createMock(HitRepository::class);
        $this->lead          = $this->createMock(Lead::class);
        $this->eventDetails  = $this->createMock(Hit::class);

        $this->factory->method('getEntityManager')->willReturn($this->entityManager);
        $this->eventDetails->method('getLead')->willReturn($this->lead);
        $this->entityManager->method('getRepository')->willReturn($this->hitRepository);
    }

    /**
     * @param array<string, mixed> $action
     *
     * @dataProvider urlHitsActionDataProvider
     */
    public function testValidateUrlPageHitsAction(array $action, bool $expectedResult): void
    {
        $this->eventDetails->method('getUrl')->willReturn('https://example.com/ppk');
        $this->hitRepository->method('getDwellTimesForUrl')->willReturn([
            'sum'     => 0,
            'min'     => 0,
            'max'     => 0,
            'average' => 0.0,
            'count'   => 1,
        ]);
        $this->hitRepository->expects($this->never())->method('getLatestHit');

        $result = PointActionHelper::validateUrlHit($this->factory, $this->eventDetails, $action);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function urlHitsActionDataProvider(): array
    {
        return [
            'url_matches_first_hit' => [
                [
                    'id'         => 2,
                    'type'       => 'url.hit',
                    'name'       => 'Hit page',
                    'properties' => [
                        'page_url'               => 'https://example.com/ppk',
                        'page_hits'              => 1,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 0,
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 5,
                ],
                true,
            ],
            'plain_text_matches_substring' => [
                [
                    'id'         => 5,
                    'type'       => 'url.hit',
                    'name'       => 'Plain text URL match',
                    'properties' => [
                        'page_url'               => 'example.com/pp',
                        'page_hits'              => 1,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 0,
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 5,
                ],
                true,
            ],
            'legacy_wildcard_still_matches' => [
                [
                    'id'         => 6,
                    'type'       => 'url.hit',
                    'name'       => 'Legacy wildcard URL match',
                    'properties' => [
                        'page_url'               => '*example.com/ppk*',
                        'page_hits'              => 1,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 0,
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 5,
                ],
                true,
            ],
            'url_does_not_match' => [
                [
                    'id'         => 3,
                    'type'       => 'url.hit',
                    'name'       => 'Invalid URL',
                    'properties' => [
                        'page_url'               => 'https://example.com/invalid',
                        'page_hits'              => 0,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 0,
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 5,
                ],
                false,
            ],
            'page_hits_works_with_historical_data_regardless_of_url' => [
                [
                    'id'         => 4,
                    'type'       => 'url.hit',
                    'name'       => 'Page Hits Historical',
                    'properties' => [
                        'page_url'               => 'https://example.com/invalid',
                        'page_hits'              => 1,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 0,
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 5,
                ],
                true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $action
     *
     * @dataProvider returnWithinActionDataProvider
     */
    public function testValidateUrlReturnWithinAction(array $action, bool $expectedResult): void
    {
        $this->eventDetails->method('getUrl')->willReturn('https://example.com/test/');
        $this->hitRepository->method('getDwellTimesForUrl')->willReturn([
            'sum'     => 0,
            'min'     => 0,
            'max'     => 0,
            'average' => 0.0,
            'count'   => 1,
        ]);

        $currentTimestamp       = time();
        $threeHoursAgoTimestamp = $currentTimestamp - (3 * 3600);
        $latestHit              = new \DateTime();
        $latestHit->setTimestamp($threeHoursAgoTimestamp);
        $this->hitRepository->method('getLatestHit')->willReturn($latestHit);

        $result = PointActionHelper::validateUrlHit($this->factory, $this->eventDetails, $action);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function returnWithinActionDataProvider(): array
    {
        return [
            'valid_return_within' => [
                [
                    'id'         => 1,
                    'type'       => 'url.hit',
                    'name'       => 'Test return within',
                    'properties' => [
                        'page_url'               => 'https://example.com/test/',
                        'page_hits'              => null,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 14400, // 4 hours in seconds
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 3,
                ],
                true,
            ],
            'invalid_return_within' => [
                [
                    'id'         => 4,
                    'type'       => 'url.hit',
                    'name'       => 'Invalid Return Within',
                    'properties' => [
                        'page_url'               => 'https://example.com/test/',
                        'page_hits'              => null,
                        'accumulative_time_unit' => 'H',
                        'accumulative_time'      => 0,
                        'returns_within_unit'    => 'H',
                        'returns_within'         => 3600, // 1 hour in seconds
                        'returns_after_unit'     => 'H',
                        'returns_after'          => 0,
                    ],
                    'points' => 3,
                ],
                false,
            ],
        ];
    }

    public function testAccumulativeTimeUsesNormalizedSqlLikePattern(): void
    {
        $this->lead->method('getId')->willReturn(42);
        $this->eventDetails->method('getUrl')->willReturn('5211new.ddev.site/other-page');

        $this->hitRepository->expects($this->once())
            ->method('getDwellTimesForUrl')
            ->with(
                '%5211new.ddev.site/sssss%',
                ['leadId' => 42]
            )
            ->willReturn([
                'sum'     => 300,
                'min'     => 300,
                'max'     => 300,
                'average' => 300,
                'count'   => 1,
            ]);

        $action = [
            'type'       => 'url.hit',
            'properties' => [
                'page_url'          => 'https://5211new.ddev.site/sssss',
                'accumulative_time' => 60,
                'page_hits'         => null,
                'returns_within'    => null,
                'returns_after'     => null,
            ],
        ];

        $result = PointActionHelper::validateUrlHit($this->factory, $this->eventDetails, $action);

        $this->assertTrue($result);
    }

    public function testAccumulativeTimeTriggersOnDifferentPageVisit(): void
    {
        $this->lead->method('getId')->willReturn(7);
        $this->eventDetails->method('getUrl')->willReturn('5211new.ddev.site/tttt');

        $this->hitRepository->method('getDwellTimesForUrl')
            ->with('%5211new.ddev.site/sssss%', ['leadId' => 7])
            ->willReturn([
                'sum'     => 120,
                'min'     => 120,
                'max'     => 120,
                'average' => 120,
                'count'   => 1,
            ]);

        $action = [
            'type'       => 'url.hit',
            'properties' => [
                'page_url'          => '5211new.ddev.site/sssss',
                'accumulative_time' => 60,
                'page_hits'         => null,
                'returns_within'    => null,
                'returns_after'     => null,
            ],
        ];

        $result = PointActionHelper::validateUrlHit($this->factory, $this->eventDetails, $action);

        $this->assertTrue($result);
    }
}
