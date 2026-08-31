<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;

final class DoNotContactFunctionalTest extends MauticMysqlTestCase
{
    public function testGetCount(): void
    {
        $segment = $this->createSegment();

        $leads   = [];
        $dnc1    = $this->createDoNotContact(1, 'email');
        $leads[] = $this->createLead('john@doe.com', $dnc1);
        $leads[] = $this->createLead('john2@doe.com', $dnc1);

        $dnc2    = $this->createDoNotContact(2, 'text');
        $leads[] = $this->createLead('john1@doe.com', $dnc2);
        $leads[] = $this->createLead('john4@doe.com', $dnc2);

        $dnc3    = $this->createDoNotContact(3, 'dnc');
        $leads[] = $this->createLead('john3@doe.com', $dnc3);

        $this->addContactsToSegment($segment, $leads);

        $this->em->flush();

        /** @var DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository(DoNotContact::class);

        $this->assertSame(3, $dncRepo->getCount());
        $this->assertSame(1, $dncRepo->getCount('email'));
        $this->assertSame(1, $dncRepo->getCount(null, $dnc1->getChannelId()));
        $this->assertSame(2, $dncRepo->getCount(null, [$dnc1->getChannelId(), $dnc2->getChannelId()]));

        $this->assertEmpty($dncRepo->getCount(null, null, null, [$segment->getId()]));
        $this->assertEmpty($dncRepo->getCount(null, null, null, $segment->getId()));
        $this->assertEmpty($dncRepo->getCount(null, $dnc1->getChannelId(), null, true));
        $this->assertEmpty($dncRepo->getCount(null, $dnc1->getChannelId(), null, [$segment->getId()], null, true));
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('test');
        $segment->setPublicName('test');
        $segment->setAlias('test-alias');

        $this->em->persist($segment);

        return $segment;
    }

    private function createLead(string $email, DoNotContact $doNotContact): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->addDoNotContactEntry($doNotContact);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createDoNotContact(int $channelId, string $channelName): DoNotContact
    {
        $doNotContact = new DoNotContact();
        $doNotContact->setDateAdded(new \DateTime());
        $doNotContact->setChannel($channelName);
        $doNotContact->setChannelId($channelId);

        $this->em->persist($doNotContact);

        return $doNotContact;
    }

    /**
     * @param Lead[] $leads
     */
    private function addContactsToSegment(LeadList $segment, array $leads): void
    {
        foreach ($leads as $lead) {
            $listLead = new ListLead();
            $listLead->setLead($lead);
            $listLead->setList($segment);
            $listLead->setDateAdded(new \DateTime());

            $this->em->persist($listLead);
        }

        $this->em->flush();
    }
}
