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
<<<<<<< HEAD
<<<<<<< HEAD
use PHPUnit\Framework\MockObject\MockObject;
=======
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
=======
use PHPUnit\Framework\MockObject\MockObject;
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)
=======
use PHPUnit\Framework\MockObject\MockObject;
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
use PHPUnit\Framework\TestCase;

class PointActionHelperTest extends TestCase
{
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)
=======
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
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
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
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
<<<<<<< HEAD
=======
    public function testValidateUrlPageHitsAction(): void
=======
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)
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

<<<<<<< HEAD
        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);

        // Mock the HitRepository and configure the getDwellTimesForUrl method to return the desired array
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->method('getDwellTimesForUrl')->willReturn([
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
=======
    /**
     * @param array<string, mixed> $action
     *
     * @dataProvider urlHitsActionDataProvider
     */
    public function testValidateUrlPageHitsAction(array $action, bool $expectedResult): void
    {
        $this->eventDetails->method('getUrl')->willReturn('https://example.com/ppk');
        $this->hitRepository->method('getDwellTimesForUrl')->willReturn([
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)
=======
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
            'sum'     => 0,
            'min'     => 0,
            'max'     => 0,
            'average' => 0.0,
            'count'   => 1,
        ]);
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD

=======
        $entityManager->method('getRepository')->willReturn($hitRepository);
=======
        $this->hitRepository->expects($this->never())->method('getLatestHit');
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)

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
<<<<<<< HEAD

        // Set up the mocks
        $factory->method('getEntityManager')->willReturn($entityManager);
        $eventDetails->method('getLead')->willReturn($lead);

        $hitRepository = $this->createMock(HitRepository::class);

        // Mock the getLatestHit method to return the current time minus 3 hours
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
=======

>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
        $currentTimestamp       = time();
        $threeHoursAgoTimestamp = $currentTimestamp - (3 * 3600);
        $latestHit              = new \DateTime();
        $latestHit->setTimestamp($threeHoursAgoTimestamp);
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
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
<<<<<<< HEAD
=======
        $hitRepository->method('getLatestHit')->willReturn($latestHit);

        $entityManager->method('getRepository')->willReturn($hitRepository);

        // Test the method
        $result = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);

        // Assert the result is true as the URL matches the pattern and meets the "returns_within" condition
        $this->assertTrue($result);
=======
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)
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

<<<<<<< HEAD
        // Assert the result is true as the time elapsed since the last hit exceeds the specified threshold
        $this->assertTrue($result);
>>>>>>> b69a60365e (fix: [DPMMA-1079] URL hit point action fixed)
=======
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
>>>>>>> c10f05cab7 (fix: [DPMMA-1079] additional tests and refactor)
=======
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
    }
}
