<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\ListLeadRepository;

final class ListLeadRepositoryTest extends MauticMysqlTestCase
{
    private ListLeadRepository $listLeadRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->listLeadRepository = static::getContainer()->get(ListLeadRepository::class);
    }

    public function testGetContactsCountBySegment(): void
    {
        $filters       = ['manually_removed' => 0];
        $contact       = new Lead();
        $segment       = new LeadList();
        $segmentMember = new ListLead();

        $segment->setName('A segment');
        $segment->setPublicName('A segment');
        $segment->setAlias('asegment');

        $segmentMember->setLead($contact);
        $segmentMember->setList($segment);
        $segmentMember->setManuallyRemoved(false);
        $segmentMember->setDateAdded(new \DateTime());

        $this->em->persist($contact);
        $this->em->persist($segment);
        $this->em->persist($segmentMember);
        $this->em->flush();

        $this->assertSame(1, $this->listLeadRepository->getContactsCountBySegment($segment->getId(), $filters));

        $segmentMember->setManuallyRemoved(true);
        $this->em->persist($segmentMember);
        $this->em->flush();

        $this->assertSame(0, $this->listLeadRepository->getContactsCountBySegment($segment->getId(), $filters));
    }
}
