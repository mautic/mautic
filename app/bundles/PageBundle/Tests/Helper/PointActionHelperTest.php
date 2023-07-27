<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Helper\PointActionHelper;
use PHPUnit\Framework\TestCase;

class PointActionHelperTest extends TestCase
{
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
            'sum'     => 0,
            'min'     => 0,
            'max'     => 0,
            'average' => 0.0,
            'count'   => 1,
        ]);
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
        $currentTimestamp       = time();
        $threeHoursAgoTimestamp = $currentTimestamp - (3 * 3600);
        $latestHit              = new \DateTime();
        $latestHit->setTimestamp($threeHoursAgoTimestamp);
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
    }
}
