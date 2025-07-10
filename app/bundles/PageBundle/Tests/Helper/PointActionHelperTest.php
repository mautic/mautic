<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Helper\PointActionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PointActionHelperTest extends TestCase
{
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
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->hitRepository = $this->createMock(HitRepository::class);
        $this->lead          = $this->createMock(Lead::class);
        $this->eventDetails  = $this->createMock(Hit::class);

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

        $pointActionHelper = new PointActionHelper($this->entityManager);
        $result            = $pointActionHelper->validateUrlHit($this->eventDetails, $action);

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
            'url_does_not_match' => [
                [
                    'id'         => 3,
                    'type'       => 'url.hit',
                    'name'       => 'Invalid URL',
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
                false,
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

        $pointActionHelper = new PointActionHelper($this->entityManager);
        $result            = $pointActionHelper->validateUrlHit($this->eventDetails, $action);

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
}
