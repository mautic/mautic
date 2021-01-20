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

use Mautic\ChannelBundle\Model\ChannelActionModel;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Translation\TranslatorInterface;

class ChannelActionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock5;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock6;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contactModelMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $doNotContactMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $translatorMock;

    /**
     * @var ChannelActionModel
     */
    private $actionModel;

    protected function setUp(): void
    {
        $this->contactMock5     = $this->createMock(Lead::class);
        $this->contactMock6     = $this->createMock(Lead::class);
        $this->contactModelMock = $this->createMock(LeadModel::class);
        $this->doNotContactMock = $this->createMock(DoNotContact::class);
        $this->translatorMock   = $this->createMock(TranslatorInterface::class);
        $this->actionModel      = new ChannelActionModel(
            $this->contactModelMock,
            $this->doNotContactMock,
            $this->translatorMock
        );

        $this->contactMock5->method('getId')->willReturn(5);
    }

    public function testUpdateEntityAccess()
    {
        $contacts = [5, 6];

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(false);

        $this->contactModelMock->expects($this->at(2))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(false);

        $this->contactModelMock->expects($this->never())
            ->method('getContactChannels');

        $this->actionModel->update($contacts, [], [], '');
    }

    public function testSubscribeContactToEmailChannel()
    {
        $contacts           = [5];
        $subscribedChannels = ['email', 'sms']; // Subscribe contact to these channels

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        // Contact is already subscribed to the SMS channel but not to email
        $this->contactModelMock->expects($this->at(2))
            ->method('getContactChannels')
            ->with($this->contactMock5)
            ->willReturn(['sms' => 'sms']);

        $this->doNotContactMock->expects($this->once())
            ->method('isContactable')
            ->with($this->contactMock5, 'email')
            ->willReturn(DNC::IS_CONTACTABLE);

        $this->doNotContactMock->expects($this->once())
            ->method('removeDncForContact')
            ->with(5, 'email');

        $this->contactModelMock->expects($this->at(3))
            ->method('getPreferenceChannels')
            ->willReturn(['Email' => 'email', 'Text Message' => 'sms']);

        $this->doNotContactMock->expects($this->never())
            ->method('addDncForContact');

        $this->actionModel->update($contacts, $subscribedChannels);
    }

    public function testSubscribeContactWhoUnsubscribedToEmailChannel()
    {
        $contacts           = [5];
        $subscribedChannels = ['email', 'sms']; // Subscribe contact to these channels

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        // Contact is already subscribed to the SMS channel but not to email
        $this->contactModelMock->expects($this->at(2))
            ->method('getContactChannels')
            ->with($this->contactMock5)
            ->willReturn(['sms' => 'sms']);

        $this->doNotContactMock->expects($this->once())
            ->method('isContactable')
            ->with($this->contactMock5, 'email')
            ->willReturn(DNC::UNSUBSCRIBED);

        $this->doNotContactMock->expects($this->never())
            ->method('removeDncForContact');

        $this->contactModelMock->expects($this->at(3))
            ->method('getPreferenceChannels')
            ->willReturn(['Email' => 'email', 'Text Message' => 'sms']);

        $this->doNotContactMock->expects($this->never())
            ->method('addDncForContact');

        $this->actionModel->update($contacts, $subscribedChannels);
    }

    public function testUnsubscribeContactFromSmsChannel()
    {
        $contacts           = [5];
        $subscribedChannels = []; // Unsubscribe contact from missing

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(2))
            ->method('getContactChannels')
            ->with($this->contactMock5)
            ->willReturn(['sms' => 'sms']);

        $this->doNotContactMock->expects($this->never())
            ->method('isContactable');

        $this->contactModelMock->expects($this->at(3))
            ->method('getPreferenceChannels')
            ->willReturn(['Email' => 'email', 'Text Message' => 'sms']);

        $this->doNotContactMock->expects($this->at(0))
            ->method('addDncForContact')
            ->with(
                5,
                'email',
                DNC::MANUAL
            );

        $this->doNotContactMock->expects($this->at(1))
            ->method('addDncForContact')
            ->with(
                5,
                'sms',
                DNC::MANUAL
            );

        $this->actionModel->update($contacts, $subscribedChannels);
    }
}
