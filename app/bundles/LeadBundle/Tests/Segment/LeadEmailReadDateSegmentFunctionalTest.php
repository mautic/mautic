<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @see https://github.com/mautic/mautic/issues/16166
 */
final class LeadEmailReadDateSegmentFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<string, array{
     *     operator: string,
     *     expectRecentOnly: bool,
     *     expectOldOnly: bool,
     *     expectBoth: bool
     * }>
     */
    public static function emailReadDateOperatorProvider(): iterable
    {
        yield 'less than 180 days ago' => [
            'operator'         => 'lt',
            'expectRecentOnly' => false,
            'expectOldOnly'    => true,
            'expectBoth'       => false,
        ];

        yield 'greater than 180 days ago' => [
            'operator'         => 'gt',
            'expectRecentOnly' => true,
            'expectOldOnly'    => false,
            'expectBoth'       => true,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('emailReadDateOperatorProvider')]
    public function testEmailReadDateFilterUsesMostRecentReadDate(
        string $operator,
        bool $expectRecentOnly,
        bool $expectOldOnly,
        bool $expectBoth,
    ): void {
        ['recentOnly' => $recentOnly, 'oldOnly' => $oldOnly, 'both' => $both] = $this->createContactsWithReadStats($operator);

        $segment = $this->createSegmentWithFilter($operator, '180 days ago');

        $this->rebuildSegment($segment);

        $memberLeadIds = $this->getSegmentMemberLeadIds($segment);

        $this->assertMembership((int) $recentOnly->getId(), $memberLeadIds, $expectRecentOnly);
        $this->assertMembership((int) $oldOnly->getId(), $memberLeadIds, $expectOldOnly);
        $this->assertMembership((int) $both->getId(), $memberLeadIds, $expectBoth);
    }

    /**
     * @return array{recentOnly: Lead, oldOnly: Lead, both: Lead}
     */
    private function createContactsWithReadStats(string $suffix): array
    {
        $email = $this->createEmail('Issue 16166 repro email '.$suffix);

        $recentOnly = $this->createLead('RecentOnly'.$suffix);
        $oldOnly    = $this->createLead('OldOnly'.$suffix);
        $both       = $this->createLead('BothReads'.$suffix);

        $this->createReadStat($recentOnly, $email, new \DateTime('-21 days'));
        $this->createReadStat($oldOnly, $email, new \DateTime('-200 days'));
        $this->createReadStat($both, $email, new \DateTime('-200 days'));
        $this->createReadStat($both, $email, new \DateTime('-21 days'));

        return [
            'recentOnly' => $recentOnly,
            'oldOnly'    => $oldOnly,
            'both'       => $both,
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
