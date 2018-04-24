<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Tests\Model;

use Mautic\ChannelBundle\Model\ContactActionModel;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $leadMock5;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $leadMock6;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $contactModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $doNotContactMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $frequencyMock;

    /**
     * @var ContactActionModel
     */
    private $actionModel;

    protected function setUp()
    {
        $this->leadMock5        = $this->createMock(Lead::class);
        $this->leadMock6        = $this->createMock(Lead::class);
        $this->contactModelMock = $this->createMock(LeadModel::class);
        $this->doNotContactMock = $this->createMock(DoNotContact::class);
        $this->frequencyMock    = $this->createMock(FrequencyRuleRepository::class);
        $this->actionModel      = new ContactActionModel(
            $this->contactModelMock,
            $this->doNotContactMock,
            $this->frequencyMock
        );
    }

    public function testAddContactsToChannelsEntityAccess()
    {
        $contacts = [5, 6];

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->leadMock5, $this->leadMock6]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->leadMock5)
            ->willReturn(false);

        $this->contactModelMock->expects($this->at(2))
            ->method('canEditContact')
            ->with($this->leadMock6)
            ->willReturn(false);

        $this->contactModelMock->expects($this->never())
            ->method('getContactChannels');

        $this->actionModel->update($contacts, [], [], '');
    }
}
