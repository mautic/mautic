<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\PageBundle\Event\UrlTokenReplaceEvent;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Functional tests for SegmentTrackingSubscriber.
 *
 * Tests the VWO segment tracking feature that appends segment IDs to tracking URLs.
 */
final class SegmentTrackingSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private const TEST_URL = 'https://example.com/page';

    private const SEGMENT_IDS_PARAM = 'segment_ids=';

    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->configParams['append_segment_id_tracking_url'] = 'testFeatureFlagIsDisabled' !== $this->name();
        parent::setUp();
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
    }

    public function testFeatureFlagIsDisabled(): void
    {
        $lead      = $this->createContactWithSegments(2);
        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);

        Assert::assertSame(self::TEST_URL, $resultUrl, 'When feature flag is disabled, URL should not be modified');
        Assert::assertStringNotContainsString('segment_ids', $resultUrl, 'URL should not contain segment_ids parameter when feature flag is disabled');
    }

    public function testFeatureFlagIsEnabled(): void
    {
        $lead      = $this->createContactWithSegments(2);
        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);

        Assert::assertStringContainsString(self::SEGMENT_IDS_PARAM, $resultUrl, 'When feature flag is enabled, segment IDs should be appended');
        Assert::assertStringStartsWith(self::TEST_URL.'?'.self::SEGMENT_IDS_PARAM, $resultUrl, 'Segment IDs should be appended as query parameter');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideUrlVariations')]
    public function testUrlVariationsHandledCorrectly(
        string $testCase,
        string $url,
        string $expectedSeparator,
        ?string $expectedContent = null,
    ): void {
        $lead      = $this->createContactWithSegments(2);
        $resultUrl = $this->dispatchUrlTokenReplaceEvent($url, $lead);

        Assert::assertStringContainsString(self::SEGMENT_IDS_PARAM, $resultUrl, $testCase);
        Assert::assertStringContainsString($expectedSeparator.self::SEGMENT_IDS_PARAM, $resultUrl, $testCase);

        if (null !== $expectedContent) {
            Assert::assertStringContainsString($expectedContent, $resultUrl, $testCase);
        }
    }

    /**
     * @return iterable<string, array{string, string, string, string|null}>
     */
    public static function provideUrlVariations(): iterable
    {
        yield 'URL with existing query parameters' => [
            'Should use & for segment_ids when query params exist',
            'https://example.com/page?utm_source=email&utm_campaign=test',
            '&',
            'utm_source=email',
        ];

        yield 'URL with fragment' => [
            'Fragment should be preserved at end',
            'https://example.com/page#section',
            '?',
            '#section',
        ];

        yield 'URL with query params and fragment' => [
            'Query params before segment_ids, fragment at end',
            'https://example.com/page?existing=param#anchor',
            '&',
            '#anchor',
        ];

        yield 'URL with special characters' => [
            'URL encoding should be preserved',
            'https://example.com/page?name=Test%20User&email=test%40example.com',
            '&',
            'Test%20User',
        ];
    }

    public function testUrlNotModifiedWhenContactHasNoSegments(): void
    {
        $lead = new Lead();
        $lead->setEmail('nosegments@example.com');
        $this->em->persist($lead);
        $this->em->flush();

        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);

        Assert::assertSame(self::TEST_URL, $resultUrl, 'URL should not be modified when contact has no segments');
        Assert::assertStringNotContainsString('segment_ids', $resultUrl, 'URL should not contain segment_ids');
    }

    public function testContactRemovedFromSegment(): void
    {
        $lead       = $this->createContactWithSegments(3);
        $segments   = $this->getContactSegments($lead);
        $segmentIds = array_keys($segments);

        $listLead = $this->em->getRepository(ListLead::class)->findOneBy([
            'lead' => $lead,
            'list' => $segmentIds[0],
        ]);

        if ($listLead) {
            $listLead->setManuallyRemoved(true);
            $this->em->persist($listLead);
            $this->em->flush();
            $this->em->clear();
        }

        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);

        $parsedUrl = parse_url($resultUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        $returnedIds = explode(',', $queryParams['segment_ids']);

        Assert::assertCount(2, $returnedIds, 'Should return 2 segment IDs after removing one');
        Assert::assertNotContains((string) $segmentIds[0], $returnedIds, 'Removed segment ID should not be present');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSegmentCounts')]
    public function testSegmentIdsSortedAndFormattedCorrectly(int $segmentCount): void
    {
        $lead      = $this->createContactWithSegments($segmentCount);
        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);

        $parsedUrl = parse_url($resultUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        $segmentIds = explode(',', $queryParams['segment_ids']);
        Assert::assertCount($segmentCount, $segmentIds, "Should return exactly {$segmentCount} segment IDs");

        foreach ($segmentIds as $id) {
            Assert::assertTrue(ctype_digit($id), "Segment ID '{$id}' should be numeric");
            Assert::assertGreaterThan(0, (int) $id, 'Segment ID should be positive');
        }

        $sortedIds = array_map('intval', $segmentIds);
        sort($sortedIds);
        Assert::assertSame($sortedIds, array_map('intval', $segmentIds), 'Segment IDs should be sorted numerically');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideSegmentCounts(): iterable
    {
        yield 'Single segment' => [1];
        yield 'Multiple segments' => [5];
    }

    public function testEventWithEmailIdParameter(): void
    {
        $lead = $this->createContactWithSegments(2);

        $event = new UrlTokenReplaceEvent(self::TEST_URL, $lead, 123);
        $this->dispatcher->dispatch($event);

        Assert::assertStringContainsString(self::SEGMENT_IDS_PARAM, $event->getContent(), 'Should append segment IDs regardless of email ID parameter');
    }

    public function testUnpublishedSegmentsNotIncluded(): void
    {
        $lead     = $this->createContactWithSegments(3);
        $segments = $this->getContactSegments($lead);

        $firstSegmentId = array_key_first($segments);
        $segment        = $this->em->getRepository(LeadList::class)->find($firstSegmentId);
        $segment->setIsPublished(false);
        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);

        Assert::assertStringContainsString(self::SEGMENT_IDS_PARAM, $resultUrl, 'Should still append segment IDs for published segments');
        Assert::assertStringNotContainsString((string) $firstSegmentId, $resultUrl, 'Unpublished segment ID should not be included');

        $parsedUrl = parse_url($resultUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $segmentIds = explode(',', $queryParams['segment_ids']);

        Assert::assertCount(2, $segmentIds, 'Should return 2 segment IDs (excluding unpublished)');
    }

    public function testSegmentChangesReflectedInRealTime(): void
    {
        $lead            = $this->createContactWithSegments(2);
        $initialSegments = $this->getContactSegments($lead);
        $initialCount    = count($initialSegments);

        $resultUrl = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);
        $parsedUrl = parse_url($resultUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $segmentIds = explode(',', $queryParams['segment_ids']);

        Assert::assertCount($initialCount, $segmentIds, 'Should initially have 2 segment IDs');

        $newSegment = new LeadList();
        $newSegment->setName(sprintf('New Segment %s', uniqid()));
        $newSegment->setAlias(sprintf('new-segment-%s', uniqid()));
        $newSegment->setPublicName(sprintf('New Segment %s', uniqid()));
        $newSegment->setIsPublished(true);
        $this->em->persist($newSegment);

        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($newSegment);
        $listLead->setDateAdded(new \DateTime());
        $listLead->setManuallyRemoved(false);
        $this->em->persist($listLead);

        $this->em->flush();
        $this->em->clear();

        $resultUrl2 = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);
        $parsedUrl2 = parse_url($resultUrl2);
        parse_str($parsedUrl2['query'], $queryParams2);
        $segmentIds2 = explode(',', $queryParams2['segment_ids']);

        Assert::assertCount($initialCount + 1, $segmentIds2, 'Should now have 3 segment IDs after adding new segment');
        Assert::assertContains((string) $newSegment->getId(), $segmentIds2, 'New segment ID should be included');

        $firstSegmentId = array_key_first($initialSegments);
        $listLead       = $this->em->getRepository(ListLead::class)->findOneBy([
            'lead' => $lead->getId(),
            'list' => $firstSegmentId,
        ]);

        if ($listLead) {
            $listLead->setManuallyRemoved(true);
            $this->em->persist($listLead);
            $this->em->flush();
            $this->em->clear();
        }

        $resultUrl3 = $this->dispatchUrlTokenReplaceEvent(self::TEST_URL, $lead);
        $parsedUrl3 = parse_url($resultUrl3);
        parse_str($parsedUrl3['query'], $queryParams3);
        $segmentIds3 = explode(',', $queryParams3['segment_ids']);

        Assert::assertCount($initialCount, $segmentIds3, 'Should have 2 segment IDs after removing one');
        Assert::assertNotContains((string) $firstSegmentId, $segmentIds3, 'Removed segment ID should not be included');
    }

    /**
     * Helper method to dispatch URL token replace event and return the result URL.
     */
    private function dispatchUrlTokenReplaceEvent(string $url, Lead $contact): string
    {
        // Ensure entity manager is in sync before dispatching event
        $this->em->flush();

        $event = new UrlTokenReplaceEvent($url, $contact, null);
        $this->dispatcher->dispatch($event);

        return $event->getContent();
    }

    /**
     * Helper method to create a contact with specified number of segments.
     */
    private function createContactWithSegments(int $segmentCount): Lead
    {
        $lead = new Lead();
        $lead->setEmail(sprintf('test%s@example.com', uniqid()));
        $lead->setFirstname('Test');
        $lead->setLastname('Contact');
        $this->em->persist($lead);

        for ($i = 0; $i < $segmentCount; ++$i) {
            $segment = new LeadList();
            $segment->setName(sprintf('Test Segment %s', uniqid()));
            $segment->setAlias(sprintf('test-segment-%s', uniqid()));
            $segment->setPublicName(sprintf('Test Segment %s', uniqid()));
            $segment->setIsPublished(true);
            $this->em->persist($segment);

            $listLead = new ListLead();
            $listLead->setLead($lead);
            $listLead->setList($segment);
            $listLead->setDateAdded(new \DateTime());
            $listLead->setManuallyRemoved(false);
            $this->em->persist($listLead);
        }

        $this->em->flush();
        $this->em->clear();

        return $this->em->getRepository(Lead::class)->find($lead->getId());
    }

    /**
     * Helper method to get segments for a contact.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getContactSegments(Lead $lead): array
    {
        /** @var \Mautic\LeadBundle\Entity\LeadListRepository $repository */
        $repository = $this->em->getRepository(LeadList::class);

        return $repository->getLeadLists($lead->getId(), false, true);
    }
}
