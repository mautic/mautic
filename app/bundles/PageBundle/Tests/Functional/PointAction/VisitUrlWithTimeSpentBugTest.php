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
 * @see https://github.com/mautic/mautic/issues/12336
 */
final class VisitUrlWithTimeSpentBugTest extends MauticMysqlTestCase
{
    public function testAccumulativeTimeTriggersOnRevisit(): void
    {
        $page    = $this->createPage('Test Page', 'test-page');
        $testUrl = 'https://example.com/test-page';

        $contact = $this->createContact('bug-visitor@example.test');

        $this->createHit($page, $contact, $testUrl, strtotime('-5 minutes'), time());
        $revisitHit = $this->createHit($page, $contact, $testUrl, time());
        $this->em->flush();

        $action = $this->createAccumulativeTimeAction($testUrl, 60);

        $this->assertTrue(
            $this->validateAction($revisitHit, $action),
            'accumulative_time should trigger on revisit when dwell time exceeds threshold'
        );
    }

    public function testAccumulativeTimeTriggersWhenVisitingDifferentPage(): void
    {
        $page       = $this->createPage('Test Page', 'test-page');
        $trackedUrl = '5211new.ddev.site/sssss';
        $otherUrl   = '5211new.ddev.site/other-page';

        $contact = $this->createContact('cross-page-visitor@example.test');

        $this->createHit($page, $contact, $trackedUrl, strtotime('-5 minutes'), time());
        $otherPageHit = $this->createHit($page, $contact, $otherUrl, time());
        $this->em->flush();

        $action = $this->createAccumulativeTimeAction($trackedUrl, 60);

        $this->assertTrue(
            $this->validateAction($otherPageHit, $action),
            'accumulative_time should trigger on any page hit once dwell time threshold is met'
        );
    }

    public function testAccumulativeTimeMatchesTrackedUrlWithoutProtocol(): void
    {
        $page        = $this->createPage('Test Page', 'test-page');
        $trackedUrl  = '5211new.ddev.site/sssss';
        $configured  = 'https://5211new.ddev.site/sssss';

        $contact = $this->createContact('protocol-mismatch@example.test');

        $this->createHit($page, $contact, $trackedUrl, strtotime('-5 minutes'), time());
        $otherPageHit = $this->createHit($page, $contact, '5211new.ddev.site/other-page', time());
        $this->em->flush();

        $action = $this->createAccumulativeTimeAction($configured, 60);

        $this->assertTrue(
            $this->validateAction($otherPageHit, $action),
            'configured URL with protocol should match tracked hits stored without protocol'
        );
    }

    /**
     * @param array<string, mixed> $action
     */
    private function validateAction(Hit $eventDetails, array $action): bool
    {
        /** @var MauticFactory&MockObject $factory */
        /** @phpstan-ignore-next-line */
        $factory = $this->createMock(MauticFactory::class);
        $factory->method('getEntityManager')->willReturn($this->em);

        return PointActionHelper::validateUrlHit($factory, $eventDetails, $action);
    }

    /**
     * @return array<string, mixed>
     */
    private function createAccumulativeTimeAction(string $pageUrl, int $accumulativeTimeSeconds): array
    {
        return [
            'type'       => 'url.hit',
            'properties' => [
                'page_url'           => $pageUrl,
                'accumulative_time'  => $accumulativeTimeSeconds,
                'page_hits'          => null,
                'returns_within'     => null,
                'returns_after'      => null,
                'first_time'         => false,
            ],
        ];
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
