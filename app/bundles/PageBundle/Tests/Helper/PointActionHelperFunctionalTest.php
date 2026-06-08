<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Helper\PointActionHelper;

final class PointActionHelperFunctionalTest extends MauticMysqlTestCase
{
    private PointActionHelper $pointActionHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pointActionHelper = $this->getContainer()->get(PointActionHelper::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideReturnsWithinAndAfterCases')]
    public function testValidateUrlHitReturnsWithinAndAfter(
        int $previousOffset,
        ?int $returnsWithin,
        ?int $returnsAfter,
        bool $expected,
        string $message,
    ): void {
        $lead = $this->createLead();
        $now  = new \DateTimeImmutable();

        // Create the previous hit (older)
        $this->createHit(
            $lead,
            'test-url',
            $now->modify($previousOffset.' seconds'),
            200,
            'test-tracking-id-1'
        );

        // Create the current hit (newer, will be used as eventDetails)
        $currentHit = $this->createHit(
            $lead,
            'test-url',
            $now,
            200,
            'test-tracking-id-2'
        );

        $action = [
            'properties' => [
                'page_url'          => 'test-url',
                'returns_within'    => $returnsWithin,
                'returns_after'     => $returnsAfter,
                'first_time'        => false,
                'accumulative_time' => null,
                'page_hits'         => null,
            ],
        ];

        $result = $this->pointActionHelper->validateUrlHit($currentHit, $action);
        $this->assertSame($expected, $result, $message);
    }

    /**
     * @return \Generator<array{int, int|null, int|null, bool, string}>
     */
    public static function provideReturnsWithinAndAfterCases(): \Generator
    {
        yield 'returns_within true' => [
            -20,
            20,
            null,
            true,
            'Should return true when returns_within is 20 and hit difference is 20',
        ];
        yield 'returns_within false' => [
            -21,
            20,
            null,
            false,
            'Should return false when returns_within is 20 and hit difference is 21',
        ];
        yield 'returns_after true' => [
            -30,
            null,
            30,
            true,
            'Should return true when returns_after is 30 and hit difference is 30',
        ];
        yield 'returns_after false' => [
            -10,
            null,
            20,
            false,
            'Should return false when returns_after is 20 and hit difference is 10',
        ];
        yield 'returns_within and returns_after both true' => [
            -30,
            40,
            30,
            true,
            'Should return true when both returns_within and returns_after are satisfied',
        ];
        yield 'returns_within true, returns_after false' => [
            -10,
            20,
            20,
            false,
            'Should return false when returns_within is true but returns_after is false',
        ];
    }

    public function testValidateUrlHitWithoutPreviousHit(): void
    {
        $lead = $this->createLead();
        $now  = new \DateTimeImmutable();

        // Create only the current hit (no previous hit)
        $currentHit = $this->createHit(
            $lead,
            'test-url',
            $now,
            200,
            'test-tracking-id-2'
        );

        // Test returns_within only
        $actionWithin = [
            'properties' => [
                'page_url'          => 'test-url',
                'returns_within'    => 20,
                'returns_after'     => null,
                'first_time'        => false,
                'accumulative_time' => null,
                'page_hits'         => null,
            ],
        ];
        $resultWithin = $this->pointActionHelper->validateUrlHit($currentHit, $actionWithin);
        $this->assertFalse($resultWithin, 'Should return false when no previous hit is present and returns_within is set');

        // Test returns_after only
        $actionAfter = [
            'properties' => [
                'page_url'          => 'test-url',
                'returns_within'    => null,
                'returns_after'     => 20,
                'first_time'        => false,
                'accumulative_time' => null,
                'page_hits'         => null,
            ],
        ];
        $resultAfter = $this->pointActionHelper->validateUrlHit($currentHit, $actionAfter);
        $this->assertFalse($resultAfter, 'Should return false when no previous hit is present and returns_after is set');
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $lead->setLastname('User');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createHit(Lead $lead, string $url, \DateTimeImmutable $dateHit, int $code, string $trackingId): Hit
    {
        $hit = new Hit();
        $hit->setLead($lead);
        $hit->setUrl($url);
        $hit->setDateHit(\DateTime::createFromImmutable($dateHit));
        $hit->setCode($code);
        $hit->setTrackingId($trackingId);
        $this->em->persist($hit);
        $this->em->flush();

        return $hit;
    }
}
