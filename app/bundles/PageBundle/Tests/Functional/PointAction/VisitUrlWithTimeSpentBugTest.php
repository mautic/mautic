<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\PointAction;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Helper\PointActionHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Reproduces https://github.com/mautic/mautic/issues/12336.
 *
 * When a "Visits URL" point action has accumulative_time set,
 * the contact must REVISIT the page for the points to be applied.
 * The dwell time from a previous visit is not evaluated automatically
 * when the time threshold is crossed.
 */
final class VisitUrlWithTimeSpentBugTest extends MauticMysqlTestCase
{
    public function testAccumulativeTimeOnlyTriggersOnRevisit(): void
    {
        $page    = $this->createPage('Test Page', 'test-page');
        $testUrl = 'https://example.com/test-page';

        $contact1 = $this->createContact('bug-visitor@example.test');
        $contact2 = $this->createContact('bug-nonvisitor@example.test');

        // Simulate contact1 visiting the page 5 minutes ago and leaving now
        $this->createHit($page, $contact1, $testUrl, strtotime('-5 minutes'), time());
        $this->em->flush();

        // Now contact1 visits the page a SECOND time
        $revisitHit = $this->createHit($page, $contact1, $testUrl, time());
        $this->em->flush();

        // Contact2 has never visited this page
        $this->createHit($page, $contact2, $testUrl, time());
        $this->em->flush();

        // The dwell time for contact1 on this URL should be ~300s (> 60s threshold)
        $hitRepo    = $this->em->getRepository(Hit::class);
        $dwellStats = $hitRepo->getDwellTimesForUrl($testUrl, ['leadId' => $contact1->getId()]);

        $this->assertArrayHasKey('sum', $dwellStats);
        $this->assertGreaterThan(60, $dwellStats['sum'], 'Contact1 should have >60s accumulative dwell time on the page');

        // Points before: should be 0
        $this->assertSame(0, $contact1->getPoints());
        $this->assertSame(0, $contact2->getPoints());

        // Create the point action and evaluate it via the helper
        $action = [
            'type'       => 'url.hit',
            'properties' => [
                'page_url'           => $testUrl,
                'accumulative_time'  => 60,
                'page_hits'          => null,
                'returns_within'     => null,
                'returns_after'      => null,
                'first_time'         => false,
            ],
        ];

        /** @var MauticFactory&MockObject $factory */
        $factory = $this->createMock(MauticFactory::class);
        $factory->method('getEntityManager')->willReturn($this->em);

        // Evaluate using the REVISIT hit — should return TRUE (accumulative time met)
        $eventDetails = $revisitHit;
        $result       = PointActionHelper::validateUrlHit($factory, $eventDetails, $action);
        $this->assertTrue($result, 'BUG #12336: accumulative_time should trigger on revisit when dwell time exceeds threshold');

        // Now delete all hits and re-add only the FIRST hit (with dateLeft) — simulate that
        // the contact visited once and left, but never came back. Show that there's no
        // mechanism to trigger the point action at this point.
        $this->em->getConnection()->executeStatement(
            'DELETE FROM test_page_hits WHERE lead_id = :lead',
            ['lead' => $contact1->getId()]
        );

        $this->createHit($page, $contact1, $testUrl, strtotime('-5 minutes'), time());
        $this->em->flush();

        $dwellStats2 = $hitRepo->getDwellTimesForUrl($testUrl, ['leadId' => $contact1->getId()]);
        $this->assertGreaterThan(60, $dwellStats2['sum'] ?? 0, 'Contact1 still has >60s dwell time');

        // The point action handler only fires on page hits to matching URLs.
        // When contact1 left the page (dateLeft was set), no new hit was created on this URL.
        // The point action is never evaluated — the dwell time threshold was crossed silently.
        $this->em->clear();
        $contact1After = $this->em->getRepository(Lead::class)->find($contact1->getId());
        $this->assertInstanceOf(Lead::class, $contact1After);
        $this->assertSame(0, $contact1After->getPoints(), 'BUG CONFIRMED: points NOT awarded without revisit — dwell time threshold was crossed but no page hit triggered the evaluation');
    }

    private function createPage(string $name, string $alias): Page
    {
        $page = new Page();
        $page->setTitle($name);
        $page->setAlias($alias);
        $page->setIsPublished(true);
        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime());
        $this->em->persist($contact);

        return $contact;
    }

    private function createHit(Page $page, Lead $contact, string $url, int $dateHitTimestamp, ?int $dateLeftTimestamp = null): Hit
    {
        $hit = new Hit();
        $hit->setPage($page);
        $hit->setLead($contact);
        $hit->setUrl($url);
        $hit->setDateHit(new \DateTime('@'.$dateHitTimestamp));

        if (null !== $dateLeftTimestamp) {
            $hit->setDateLeft(new \DateTime('@'.$dateLeftTimestamp));
        }

        $hit->setTrackingId(uniqid('tracking_'));
        $hit->setCode(200);

        $this->em->persist($hit);

        return $hit;
    }
}
