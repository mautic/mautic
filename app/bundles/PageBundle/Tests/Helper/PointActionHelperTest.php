<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Helper\PointActionHelper;
<<<<<<< HEAD
use PHPUnit\Framework\MockObject\MockObject;
=======
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
use PHPUnit\Framework\TestCase;

class PointActionHelperTest extends TestCase
{
<<<<<<< HEAD
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
=======
    public function testValidateUrlPageHitsAction(): void
    {
        // Mock the required objects
        $factory       = $this->createMock(MauticFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $lead          = $this->createMock(Lead::class);
        $eventDetails  = $this->createMock(Hit::class);
        $eventDetails->method('getUrl')->willReturn('http://localhost:7000/ppk');

        // Set the action properties for the test scenario
        $action = [
            'id'         => 2,
            'type'       => 'url.hit',
            'name'       => 'Hit page',
            'properties' => [
                'page_url'               => 'http://localhost:7000/ppk',
                'page_hits'              => 1,
                'accumulative_time_unit' => 'H',
                'accumulative_time'      => 0,
                'returns_within_unit'    => 'H',
                'returns_within'         => 0,
                'returns_after_unit'     => 'H',
                'returns_after'          => 0,
            ],
            'points' => 5,
        ];

        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);

        // Mock the HitRepository and configure the getDwellTimesForUrl method to return the desired array
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->method('getDwellTimesForUrl')->willReturn([
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
            'sum'     => 0,
            'min'     => 0,
            'max'     => 0,
            'average' => 0.0,
            'count'   => 1,
        ]);
<<<<<<< HEAD

=======
        $entityManager->method('getRepository')->willReturn($hitRepository);

        // Getting the latest Hit is not needed for this action
        $hitRepository->expects($this->never())->method('getLatestHit');

        // Test the method
        $result = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);

        // Assert the result is true as the URL matches the pattern and meets the conditions for the first hit
        $this->assertTrue($result);
    }

    public function testValidateUrlReturnWithinAction(): void
    {
        // Mock the required objects
        $factory       = $this->createMock(MauticFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $lead          = $this->createMock(Lead::class);
        $eventDetails  = $this->createMock(Hit::class);
        $eventDetails->method('getUrl')->willReturn('https://example.com/test/');

        // Set the action properties for the test scenario
        $action = [
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
        ];

        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);

        $hitRepository = $this->createMock(HitRepository::class);

        // Mock the getLatestHit method to return the current time minus 3 hours
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
        $currentTimestamp       = time();
        $threeHoursAgoTimestamp = $currentTimestamp - (3 * 3600);
        $latestHit              = new \DateTime();
        $latestHit->setTimestamp($threeHoursAgoTimestamp);
<<<<<<< HEAD
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
=======
        $hitRepository->method('getLatestHit')->willReturn($latestHit);

        $entityManager->method('getRepository')->willReturn($hitRepository);

        // Test the method
        $result = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);

        // Assert the result is true as the URL matches the pattern and meets the "returns_within" condition
        $this->assertTrue($result);
    }

    public function testValidateUrlReturnWithinActionWhenNoLastHitFound(): void
    {
        // Mock the required objects
        $factory       = $this->createMock(MauticFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $lead          = $this->createMock(Lead::class);
        $eventDetails  = $this->createMock(Hit::class);
        $eventDetails->method('getUrl')->willReturn('https://example.com/test/');

        // Set the action properties for the test scenario
        $action = [
            'id'         => 1,
            'type'       => 'url.hit',
            'name'       => 'Test return within when no last hit found',
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
        ];

        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->method('getLatestHit')->willReturn(null);
        $entityManager->method('getRepository')->willReturn($hitRepository);

        $result = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);

        // Assert the result is false as this was the first URL hit
        $this->assertFalse($result);
    }

    public function testValidateUrlAccumulativeTimeAction(): void
    {
        // Mock the required objects
        $factory       = $this->createMock(MauticFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $lead          = $this->createMock(Lead::class);
        $eventDetails  = $this->createMock(Hit::class);
        $eventDetails->method('getUrl')->willReturn('http://localhost:7000/ppk/');

        // Set the action properties for the test scenario
        $action = [
            'id'         => 1,
            'type'       => 'url.hit',
            'name'       => 'Test accumulative time',
            'properties' => [
                'page_url'               => 'http://localhost:7000/ppk/',
                'page_hits'              => null,
                'accumulative_time_unit' => 'H',
                'accumulative_time'      => 7200, // 2 hours in seconds
                'returns_within_unit'    => 'H',
                'returns_within'         => 0,
                'returns_after_unit'     => 'H',
                'returns_after'          => 0,
            ],
            'points' => 3,
        ];

        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);

        // Mock the HitRepository and configure the getDwellTimesForUrl method to return the desired array
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->method('getDwellTimesForUrl')->willReturn([
            'sum'     => 14400, // 4 hours in seconds
            'min'     => 3600, // 1 hour in seconds
            'max'     => 7200, // 2 hours in seconds
            'average' => 3600.0, // 1 hour in seconds
            'count'   => 4, // 4 hits recorded
        ]);

        // Getting the latest Hit is not needed for this action
        $hitRepository->expects($this->never())->method('getLatestHit');

        $entityManager->method('getRepository')->willReturn($hitRepository);

        // Test the method
        $result = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);

        // Assert the result is true as the accumulated time exceeds the specified threshold
        $this->assertTrue($result);
    }

    public function testValidateUrlReturnsAfterAction(): void
    {
        // Mock the required objects
        $factory       = $this->createMock(MauticFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $lead          = $this->createMock(Lead::class);
        $eventDetails  = $this->createMock(Hit::class);
        $eventDetails->method('getUrl')->willReturn('http://localhost:7000/ppk/');

        // Set the action properties for the test scenario
        $action = [
            'id'         => 1,
            'type'       => 'url.hit',
            'name'       => 'Test returns after',
            'properties' => [
                'page_url'               => 'http://localhost:7000/ppk/',
                'page_hits'              => null,
                'accumulative_time_unit' => 'H',
                'accumulative_time'      => 0,
                'returns_within_unit'    => 'H',
                'returns_within'         => 0,
                'returns_after_unit'     => 'H',
                'returns_after'          => 7200, // 2 hours in seconds
            ],
            'points' => 3,
        ];

        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);

        // Mock the HitRepository and configure the getDwellTimesForUrl method to return the desired array
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->method('getDwellTimesForUrl')->willReturn([
            'sum'     => 0,
            'min'     => 0,
            'max'     => 0,
            'average' => 0.0,
            'count'   => 1, // Only one hit recorded
        ]);

        // Mock the getLatestHit method to return a DateTime object representing 3 hours ago
        $currentTimestamp       = time();
        $threeHoursAgoTimestamp = $currentTimestamp - (3 * 3600);
        $latestHit              = new \DateTime();
        $latestHit->setTimestamp($threeHoursAgoTimestamp);
        $hitRepository->method('getLatestHit')->willReturn($latestHit);

        $entityManager->method('getRepository')->willReturn($hitRepository);

        // Test the method
        $result = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);

        // Assert the result is true as the time elapsed since the last hit exceeds the specified threshold
        $this->assertTrue($result);
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
    }
}
