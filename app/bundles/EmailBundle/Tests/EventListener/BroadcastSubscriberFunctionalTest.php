<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\EventListener\BroadcastSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Output\BufferedOutput;

final class BroadcastSubscriberFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testOnBroadcastResetsEventDefaultsPerEmailWhenAbTestingMutatesParameters(): void
    {
        $abSegment      = $this->createSegment('ab-segment-functional', []);
        $regularSegment = $this->createSegment('regular-segment-functional', []);

        $abLeadA = $this->createLead('Ab', 'One', 'ab.one@example.com');
        $abLeadB = $this->createLead('Ab', 'Two', 'ab.two@example.com');

        $regularLeadA = $this->createLead('Regular', 'One', 'regular.one@example.com');
        $regularLeadB = $this->createLead('Regular', 'Two', 'regular.two@example.com');
        $regularLeadC = $this->createLead('Regular', 'Three', 'regular.three@example.com');
        $regularLeadD = $this->createLead('Regular', 'Four', 'regular.four@example.com');

        $membershipDate = new \DateTime('-1 day');
        $this->addLeadToSegment($abSegment, $abLeadA, $membershipDate);
        $this->addLeadToSegment($abSegment, $abLeadB, $membershipDate);

        $this->addLeadToSegment($regularSegment, $regularLeadA, $membershipDate);
        $this->addLeadToSegment($regularSegment, $regularLeadB, $membershipDate);
        $this->addLeadToSegment($regularSegment, $regularLeadC, $membershipDate);
        $this->addLeadToSegment($regularSegment, $regularLeadD, $membershipDate);

        // Persist segments and memberships before broadcasting.
        $this->em->flush();

        $abParentEmail = $this->createListEmail('AB Parent Functional', $abSegment);
        $abParentEmail->setVariantSettings([
            'enableAbTest'    => true,
            'totalWeight'     => 90,
            'winnerCriteria'  => 'email.openrate',
            'sendWinnerDelay' => 1,
        ]);

        $regularEmail = $this->createListEmail('Regular Broadcast Functional', $regularSegment);

        $this->em->persist($abParentEmail);
        $this->em->persist($regularEmail);
        $this->em->flush();

        $this->em->clear();

        $event = new ChannelBroadcastEvent('email', null, new BufferedOutput());
        $event->setLimit(5);
        $event->setBatch(10);
        $event->setThreadId(1);
        $event->setMaxThreads(1);

        $subscriber = self::getContainer()->get(BroadcastSubscriber::class);
        self::assertInstanceOf(BroadcastSubscriber::class, $subscriber);

        $subscriber->onBroadcast($event);
        $results = $event->getResults();
        Assert::assertNotEmpty($results);

        $abResult      = $this->getResultForEmailName($results, 'AB Parent Functional');
        $regularResult = $this->getResultForEmailName($results, 'Regular Broadcast Functional');

        Assert::assertNotNull($abResult, print_r(array_keys($results), true));
        Assert::assertNotNull($regularResult);

        Assert::assertSame(2, (int) $abResult['success'] + (int) $abResult['failed']);
        Assert::assertSame(4, (int) $regularResult['success'] + (int) $regularResult['failed']);
    }

    private function createListEmail(string $name, LeadList $segment): Email
    {
        $email = $this->createEmail($name);
        $email->setEmailType('list');
        $email->setPublishUp(new \DateTime());
        $email->addList($segment);

        return $email;
    }

    private function addLeadToSegment(LeadList $segment, Lead $lead, \DateTime $dateAdded): void
    {
        $listLead = new ListLead();
        $listLead->setList($segment);
        $listLead->setLead($lead);
        $listLead->setDateAdded($dateAdded);

        $this->em->persist($listLead);
    }

    /**
     * @param array<string, array{success:int, failed:int, failedRecipientsByList:array<int, array<int, string>>}> $results
     *
     * @return array<string, int|array<int, array<int, string>>>|null
     */
    private function getResultForEmailName(array $results, string $emailName): ?array
    {
        foreach ($results as $channelLabel => $counts) {
            if (str_contains($channelLabel, $emailName)) {
                return $counts;
            }
        }

        return null;
    }
}
