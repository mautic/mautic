<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignLeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\NullLogger;

class InactiveContactFinderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadRepository
     */
    private $leadRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CampaignLeadRepository
     */
    private $campaignLeadRepository;

    protected function setUp()
    {
        $this->leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->campaignRepository = $this->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->campaignLeadRepository = $this->getMockBuilder(CampaignLeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoContactsFoundExceptionIsThrown()
    {
        $this->campaignLeadRepository->expects($this->once())
            ->method('getInactiveContacts')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, new Event(), $limiter);
    }

    public function testNoContactsFoundExceptionIsThrownIfEntitiesAreNotFound()
    {
        $contactMemberDates = [
            1 => new \DateTime(),
        ];

        $this->campaignLeadRepository->expects($this->once())
            ->method('getInactiveContacts')
            ->willReturn($contactMemberDates);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, new Event(), $limiter);
    }

    public function testContactsAreFoundAndStoredInCampaignMemberDatesAdded()
    {
        $contactMemberDates = [
            1 => new \DateTime(),
        ];

        $this->campaignLeadRepository->expects($this->once())
            ->method('getInactiveContacts')
            ->willReturn($contactMemberDates);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead()]));

        $contactFinder = $this->getContactFinder();

        $limiter  = new ContactLimiter(0, 0, 0, 0);
        $contacts = $contactFinder->getContacts(1, new Event(), $limiter);
        $this->assertCount(1, $contacts);

        $this->assertEquals($contactMemberDates, $contactFinder->getDatesAdded());
    }

    /**
     * @return InactiveContactFinder
     */
    private function getContactFinder()
    {
        return new InactiveContactFinder(
            $this->leadRepository,
            $this->campaignRepository,
            $this->campaignLeadRepository,
            new NullLogger()
        );
    }
}
