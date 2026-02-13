<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Stats\Helper;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\EmailBundle\Stats\Helper\BouncedHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;

/**
 * Tests email bounce statistics generation with various filters and permissions.
 */
final class BouncedHelperTest extends MauticMysqlTestCase
{
    private BouncedHelper $bouncedHelper;
    private User $adminUser;
    private User $regularUser;
    private Email $email;
    private Campaign $campaign;
    private Lead $lead1;
    private Lead $lead2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bouncedHelper = static::getContainer()->get('mautic.email.stats.helper_bounced');

        $this->createUsers();
        $this->createEmailAndCampaign();
        $this->createLeadsAndStats();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statsFilterProvider')]
    public function testStatsWithFilters(
        bool $canViewOthers,
        bool $useCampaignFilter,
        string $dateFrom,
        string $dateTo,
        int $expectedCount,
        string $message,
    ): void {
        $fromDateTime = new \DateTime($dateFrom);
        $toDateTime   = new \DateTime($dateTo);

        $options = new EmailStatOptions();
        $options->setCanViewOthers($canViewOthers);
        $options->setEmailIds([$this->email->getId()]);

        if ($useCampaignFilter) {
            $options->setCampaignId($this->campaign->getId());
        }

        $statCollection = new StatCollection();

        $this->bouncedHelper->generateStats($fromDateTime, $toDateTime, $options, $statCollection);

        $sum = $this->getSumFromStatCollection($statCollection);

        $this->assertSame($expectedCount, $sum, $message);
    }

    /**
     * @return \Generator<string, array{canViewOthers: bool, useCampaignFilter: bool, dateFrom: string, dateTo: string, expectedCount: int, message: string}>
     */
    public static function statsFilterProvider(): \Generator
    {
        yield 'admin can view all stats' => [
            'canViewOthers'     => true,
            'useCampaignFilter' => false,
            'dateFrom'          => '-7 days',
            'dateTo'            => '+1 day',
            'expectedCount'     => 2,
            'message'           => 'Admin should see all 2 bounce records',
        ];

        yield 'regular user cannot view others stats' => [
            'canViewOthers'     => false,
            'useCampaignFilter' => false,
            'dateFrom'          => '-7 days',
            'dateTo'            => '+1 day',
            'expectedCount'     => 0,
            'message'           => 'Regular user without view others permission should see no stats for admin-created email',
        ];

        yield 'filter by campaign' => [
            'canViewOthers'     => true,
            'useCampaignFilter' => true,
            'dateFrom'          => '-7 days',
            'dateTo'            => '+1 day',
            'expectedCount'     => 2,
            'message'           => 'Should return stats filtered by campaign',
        ];

        yield 'filter by future date range' => [
            'canViewOthers'     => true,
            'useCampaignFilter' => false,
            'dateFrom'          => '+1 day',
            'dateTo'            => '+7 days',
            'expectedCount'     => 0,
            'message'           => 'Should return no stats for future date range',
        ];
    }

    public function testFilterByEmailId(): void
    {
        // Create another email
        $anotherEmail = new Email();
        $anotherEmail->setName('Another Email');
        $anotherEmail->setSubject('Another Subject');
        $anotherEmail->setCustomHtml('<html><body>Another Content</body></html>');
        $anotherEmail->setEmailType('template');
        $this->em->persist($anotherEmail);
        $this->em->flush();

        $fromDateTime = new \DateTime('-7 days');
        $toDateTime   = new \DateTime('+1 day');

        $options = new EmailStatOptions();
        $options->setCanViewOthers(true);
        $options->setEmailIds([$this->email->getId()]);

        $statCollection = new StatCollection();

        $this->bouncedHelper->generateStats($fromDateTime, $toDateTime, $options, $statCollection);

        $sum = $this->getSumFromStatCollection($statCollection);

        $this->assertSame(2, $sum, 'Should only return stats for the specified email');
    }

    public function testOnlyBounceReasonIncluded(): void
    {
        // Add an unsubscribed contact
        $unsubscribedContact = new DoNotContact();
        $unsubscribedContact->setLead($this->lead1);
        $unsubscribedContact->setChannel('email');
        $unsubscribedContact->setChannelId($this->email->getId());
        $unsubscribedContact->setReason(DoNotContact::UNSUBSCRIBED);
        $unsubscribedContact->setDateAdded(new \DateTime());
        $this->em->persist($unsubscribedContact);
        $this->em->flush();

        $fromDateTime = new \DateTime('-7 days');
        $toDateTime   = new \DateTime('+1 day');

        $options = new EmailStatOptions();
        $options->setCanViewOthers(true);
        $options->setEmailIds([$this->email->getId()]);

        $statCollection = new StatCollection();

        $this->bouncedHelper->generateStats($fromDateTime, $toDateTime, $options, $statCollection);

        $sum = $this->getSumFromStatCollection($statCollection);

        // Should still be 2, not 3, because unsubscribed should not be counted
        $this->assertSame(2, $sum, 'Should only count BOUNCED reason, not UNSUBSCRIBED');
    }

    private function createUsers(): void
    {
        // Create admin role
        $adminRole = new Role();
        $adminRole->setName('Admin');
        $adminRole->setIsAdmin(true);
        $this->em->persist($adminRole);

        // Create regular role
        $regularRole = new Role();
        $regularRole->setName('Regular');
        $regularRole->setIsAdmin(false);
        $this->em->persist($regularRole);

        // Create admin user
        $this->adminUser = new User();
        $this->adminUser->setUsername('admin_test');
        $this->adminUser->setFirstName('Admin');
        $this->adminUser->setLastName('User');
        $this->adminUser->setEmail('admin@test.com');
        $this->adminUser->setPassword('password');
        $this->adminUser->setRole($adminRole);
        $this->em->persist($this->adminUser);

        // Create regular user
        $this->regularUser = new User();
        $this->regularUser->setUsername('regular_test');
        $this->regularUser->setFirstName('Regular');
        $this->regularUser->setLastName('User');
        $this->regularUser->setEmail('regular@test.com');
        $this->regularUser->setPassword('password');
        $this->regularUser->setRole($regularRole);
        $this->em->persist($this->regularUser);

        $this->em->flush();
    }

    private function createEmailAndCampaign(): void
    {
        // Create campaign
        $this->campaign = new Campaign();
        $this->campaign->setName('Test Campaign');
        $this->campaign->setCreatedBy($this->adminUser);
        $this->em->persist($this->campaign);

        // Create email
        $this->email = new Email();
        $this->email->setName('Test Email');
        $this->email->setSubject('Test Subject');
        $this->email->setCustomHtml('<html><body>Test Content</body></html>');
        $this->email->setEmailType('template');
        $this->email->setCreatedBy($this->adminUser);
        $this->em->persist($this->email);

        // Create campaign event
        $event = new Event();
        $event->setName('Send Email');
        $event->setType('email.send');
        $event->setCampaign($this->campaign);
        $event->setEventType('action');
        $event->setProperties(['email' => $this->email->getId()]);
        $this->em->persist($event);

        $this->em->flush();
    }

    private function createLeadsAndStats(): void
    {
        // Create leads
        $this->lead1 = new Lead();
        $this->lead1->setEmail('lead1@test.com');
        $this->lead1->setFirstname('Lead');
        $this->lead1->setLastname('One');
        $this->em->persist($this->lead1);

        $this->lead2 = new Lead();
        $this->lead2->setEmail('lead2@test.com');
        $this->lead2->setFirstname('Lead');
        $this->lead2->setLastname('Two');
        $this->em->persist($this->lead2);

        $this->em->flush();

        // Get the campaign event we created in createEmailAndCampaign
        $event = $this->em->getRepository(Event::class)
            ->findOneBy(['campaign' => $this->campaign]);

        // Create campaign_lead_event_log entries (required for campaign filter)
        $leadEventLog1 = new LeadEventLog();
        $leadEventLog1->setEvent($event);
        $leadEventLog1->setLead($this->lead1);
        $leadEventLog1->setCampaign($this->campaign);
        $leadEventLog1->setDateTriggered(new \DateTime());
        $this->em->persist($leadEventLog1);

        $leadEventLog2 = new LeadEventLog();
        $leadEventLog2->setEvent($event);
        $leadEventLog2->setLead($this->lead2);
        $leadEventLog2->setCampaign($this->campaign);
        $leadEventLog2->setDateTriggered(new \DateTime());
        $this->em->persist($leadEventLog2);

        $this->em->flush();

        // Create email stats with campaign.event reference
        $stat1 = new Stat();
        $stat1->setEmail($this->email);
        $stat1->setLead($this->lead1);
        $stat1->setEmailAddress($this->lead1->getEmail());
        $stat1->setDateSent(new \DateTime());
        $stat1->setSource('campaign.event');
        $stat1->setSourceId($event->getId());
        $this->em->persist($stat1);

        $stat2 = new Stat();
        $stat2->setEmail($this->email);
        $stat2->setLead($this->lead2);
        $stat2->setEmailAddress($this->lead2->getEmail());
        $stat2->setDateSent(new \DateTime());
        $stat2->setSource('campaign.event');
        $stat2->setSourceId($event->getId());
        $this->em->persist($stat2);

        $this->em->flush();

        // Create bounce records
        $dnc1 = new DoNotContact();
        $dnc1->setLead($this->lead1);
        $dnc1->setChannel('email');
        $dnc1->setChannelId($this->email->getId());
        $dnc1->setReason(DoNotContact::BOUNCED);
        $dnc1->setDateAdded(new \DateTime());
        $this->em->persist($dnc1);

        $dnc2 = new DoNotContact();
        $dnc2->setLead($this->lead2);
        $dnc2->setChannel('email');
        $dnc2->setChannelId($this->email->getId());
        $dnc2->setReason(DoNotContact::BOUNCED);
        $dnc2->setDateAdded(new \DateTime());
        $this->em->persist($dnc2);

        $this->em->flush();
    }

    private function getSumFromStatCollection(StatCollection $statCollection): int
    {
        $sum   = 0;
        $years = $statCollection->getStats()->getYears();
        foreach ($years as $year) {
            $sum += $year->getSum();
        }

        return $sum;
    }
}
