<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Membership\Action;

use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Remover;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;

class RemoverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LeadRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $leadRepository;

    protected function setUp()
    {
        $this->leadRepository = $this->createMock(LeadRepository::class);
    }

    public function testMemberHasDateExitedSetWithForcedExit()
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(false);

        $this->getRemover()->updateExistingMembership($campaignMember, true);

        $this->assertInstanceOf(\DateTime::class, $campaignMember->getDateLastExited());
    }

    public function testMemberHasDateExistedSetToNullWhenRemovedByFilter()
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(false);

        $this->getRemover()->updateExistingMembership($campaignMember, false);

        $this->assertNull($campaignMember->getDateLastExited());
    }

    public function testExceptionThrownWhenMemberIsAlreadyRemoved()
    {
        $this->expectException(ContactAlreadyRemovedFromCampaignException::class);

        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);

        $this->getRemover()->updateExistingMembership($campaignMember, false);
    }

    /**
     * @return Remover
     */
    private function getRemover()
    {
        return new Remover($this->leadRepository);
    }
}
