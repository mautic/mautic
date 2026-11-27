<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @see https://github.com/mautic/mautic/issues/16166
 */
final class LeadEmailReadDateSegmentFunctionalTest extends MauticMysqlTestCase
{
    private const CUTOFF = '2024-08-01 20:50:00';

    /**
     * @return iterable<string, array{
     *     operator: string,
     *     expectRecentOnly: bool,
     *     expectOldOnly: bool,
     *     expectBoth: bool,
     *     expectAtCutoff: bool
     * }>
     */
    public static function emailReadDateOperatorProvider(): iterable
    {
        yield 'before cutoff' => [
            'operator'         => 'lt',
            'expectRecentOnly' => false,
            'expectOldOnly'    => true,
            'expectBoth'       => false,
            'expectAtCutoff'   => false,
        ];

        yield 'on or before cutoff' => [
            'operator'         => 'lte',
            'expectRecentOnly' => false,
            'expectOldOnly'    => true,
            'expectBoth'       => false,
            'expectAtCutoff'   => true,
        ];

        yield 'after cutoff' => [
            'operator'         => 'gt',
            'expectRecentOnly' => true,
            'expectOldOnly'    => false,
            'expectBoth'       => true,
            'expectAtCutoff'   => false,
        ];

        yield 'on or after cutoff' => [
            'operator'         => 'gte',
            'expectRecentOnly' => true,
            'expectOldOnly'    => false,
            'expectBoth'       => true,
            'expectAtCutoff'   => true,
        ];
    }

    #[DataProvider('emailReadDateOperatorProvider')]
    public function testEmailReadDateFilterUsesMostRecentReadDate(
        string $operator,
        bool $expectRecentOnly,
        bool $expectOldOnly,
        bool $expectBoth,
        bool $expectAtCutoff,
    ): void {
        ['recentOnly' => $recentOnly, 'oldOnly' => $oldOnly, 'both' => $both, 'atCutoff' => $atCutoff] = $this->createContactsWithReadStats($operator);

        $segment = $this->createSegmentWithFilter($operator, self::CUTOFF);

        $segmentService = self::getContainer()->get(ContactSegmentService::class);
        $sql            = $segmentService->getNewLeadListLeadsQueryBuilder($segment, [])->getSQL();
        if ($expectRecentOnly) {
            $this->assertStringContainsString('IN (SELECT', $sql);
            $this->assertStringContainsString('.lead_id IS NOT NULL', $sql);
            $this->assertStringNotContainsStringIgnoringCase('MAX(', $sql);
        } else {
            $this->assertStringContainsStringIgnoringCase('MAX(DISTINCT', $sql);
        }

        $this->rebuildSegment($segment);

        $memberLeadIds = $this->getSegmentMemberLeadIds($segment);

        $this->assertMembership($recentOnly->getId(), $memberLeadIds, $expectRecentOnly);
        $this->assertMembership($oldOnly->getId(), $memberLeadIds, $expectOldOnly);
        $this->assertMembership($both->getId(), $memberLeadIds, $expectBoth);
        $this->assertMembership($atCutoff->getId(), $memberLeadIds, $expectAtCutoff);
    }

    /**
     * @return array{recentOnly: Lead, oldOnly: Lead, both: Lead, atCutoff: Lead}
     */
    private function createContactsWithReadStats(string $suffix): array
    {
        $email = $this->createEmail('Issue 16166 repro email '.$suffix);

        $recentOnly = $this->createLead('RecentOnly'.$suffix);
        $oldOnly    = $this->createLead('OldOnly'.$suffix);
        $both       = $this->createLead('BothReads'.$suffix);
        $atCutoff   = $this->createLead('AtCutoff'.$suffix);

        $this->createReadStat($recentOnly, $email, new \DateTime('2024-08-01 20:50:01'));
        $this->createReadStat($oldOnly, $email, new \DateTime('2024-08-01 20:49:59'));
        $this->createReadStat($both, $email, new \DateTime('2024-08-01 20:49:59'));
        $this->createReadStat($both, $email, new \DateTime('2024-08-01 20:50:01'));
        $this->createReadStat($atCutoff, $email, new \DateTime(self::CUTOFF));

        return [
            'recentOnly' => $recentOnly,
            'oldOnly'    => $oldOnly,
            'both'       => $both,
            'atCutoff'   => $atCutoff,
        ];
    }

    /**
     * @param int[] $memberLeadIds
     */
    private function assertMembership(int $leadId, array $memberLeadIds, bool $expectedMember): void
    {
        if ($expectedMember) {
            $this->assertContains($leadId, $memberLeadIds);
        } else {
            $this->assertNotContains($leadId, $memberLeadIds);
        }
    }

    private function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createLead(string $suffix): Lead
    {
        $lead = new Lead();
        $lead->setEmail(strtolower($suffix).'@issue16166.test');
        $lead->setFirstname($suffix);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createReadStat(Lead $lead, Email $email, \DateTime $dateRead): void
    {
        $stat = new Stat();
        $stat->setLead($lead);
        $stat->setEmail($email);
        $stat->setEmailAddress((string) $lead->getEmail());
        $stat->setDateSent(clone $dateRead);
        $stat->setDateRead(clone $dateRead);
        $stat->setIsRead(true);
        $stat->setOpenCount(1);
        $stat->setTrackingHash(uniqid('hash_', true));
        $this->em->persist($stat);
        $this->em->flush();
    }

    private function createSegmentWithFilter(string $operator, string $filterValue): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Issue 16166 segment '.$operator);
        $segment->setAlias('issue_16166_'.$operator.'_'.uniqid());
        $segment->setPublicName('Issue 16166 segment '.$operator);
        $segment->setIsPublished(true);
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'lead_email_read_date',
                'object'   => 'lead',
                'type'     => 'datetime',
                'operator' => $operator,
                'filter'   => $filterValue,
                'display'  => null,
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function rebuildSegment(LeadList $segment): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $exitCode = $applicationTester->run([
            'command' => 'mautic:segments:update',
            '-i'      => $segment->getId(),
        ]);

        $this->assertSame(0, $exitCode, $applicationTester->getDisplay());
    }

    /**
     * @return int[]
     */
    private function getSegmentMemberLeadIds(LeadList $segment): array
    {
        return array_map(
            static fn (ListLead $member): int => (int) $member->getLead()->getId(),
            $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()])
        );
    }
}
