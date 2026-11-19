<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Executioner\Scheduler\Mode;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Reproduces a bug where a contact whose preferred timezone is ahead of UTC
 * (e.g. Asia/Tokyo, UTC+9) receives a weekday-restricted, single-hour campaign
 * email at the wrong local time.
 *
 * Root cause: getExecutionDateTimeFromHour() returns the original scheduled
 * UTC-derived time when that time has already passed the configured send-hour
 * in the contact's timezone, instead of advancing to the next day at the
 * configured hour (as documented and as getExecutionDateTimeBetweenHours does).
 *
 * Scenario from ticket:
 *  - Decision fires Friday 2026-07-10 09:13 UTC = Friday 18:13 JST.
 *  - Action is scheduled 1 day later → Saturday 2026-07-11 09:13 UTC = Saturday 18:13 JST.
 *  - Saturday is restricted (weekdays only). The scheduler must advance to Monday.
 *  - 18:13 JST is past the 09:00 AM send hour.
 *  - Expected trigger date: Monday 2026-07-13 09:00 JST = Monday 2026-07-13 00:00 UTC.
 *  - Buggy result:          Monday 2026-07-13 18:13 JST = Monday 2026-07-13 09:13 UTC.
 */
final class IntervalWeekdayHourTimezoneTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testHourRestrictionIsAppliedAfterDayOfWeekReschedulingForContactWithNonUtcTimezone(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Weekday 9AM email campaign');

        // Action event: 1-day interval, send at 09:00, weekdays only (Mon–Fri).
        // DOW values: Sun=0, Mon=1, Tue=2, Wed=3, Thu=4, Fri=5, Sat=6
        $event = new Event();
        $event->setName('Send email weekday 9AM');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setType('lead.changepoints');
        $event->setCampaign($campaign);
        $event->setTriggerInterval(1);
        $event->setTriggerIntervalUnit('d');
        $event->setTriggerHour('09:00:00');
        $event->setTriggerRestrictedDaysOfWeek([1, 2, 3, 4, 5]); // Mon–Fri

        $contact = new Lead();
        $contact->setTimezone('Asia/Tokyo'); // UTC+9

        $this->em->persist($contact);
        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->flush();

        $intervalScheduler = $this->getContainer()->get(Interval::class);
        $this->assertInstanceOf(Interval::class, $intervalScheduler);

        $contacts = new ArrayCollection([$contact]);

        // The initial interval (1d) from Friday 2026-07-10 09:13 UTC produces
        // Saturday 2026-07-11 09:13 UTC = Saturday 18:13 JST.
        // Saturday is restricted and 18:13 JST is past the 09:00 send-hour.
        $scheduledSaturdayUtc = new \DateTime('2026-07-11 09:13:18', new \DateTimeZone('UTC'));

        $groups = $intervalScheduler->groupContactsByDate($event, $contacts, $scheduledSaturdayUtc);

        $this->assertCount(1, $groups, 'Expected exactly one execution-date group.');

        /** @var \DateTime $executionDate */
        $executionDate = array_values($groups)[0]->getExecutionDate();

        $executionDateJst = clone $executionDate;
        $executionDateJst->setTimezone(new \DateTimeZone('Asia/Tokyo'));

        $this->assertSame('Monday', $executionDateJst->format('l'), 'Event must be rescheduled to Monday (the next allowed weekday after Saturday).');
        $this->assertSame('09:00', $executionDateJst->format('H:i'), 'Event must fire at 09:00 AM JST. '
        .'The bug causes it to fire at 18:13 JST because the DOW loop advances the day '
        .'without resetting the time to the configured send-hour.');
    }
}
