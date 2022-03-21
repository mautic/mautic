<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Model;

use Mautic\ChannelBundle\Model\ChannelActionModel;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

class ChannelActionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    private $contactMock5;

    /**
     * @var MockObject
     */
    private $contactMock6;

    /**
     * @var MockObject
     */
    private $contactModelMock;

    /**
     * @var MockObject
     */
    private $doNotContactMock;

    /**
     * @var MockObject
     */
    private $translatorMock;

    /**
     * @var ChannelActionModel
     */
    private $actionModel;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function testUpdateEntityAccess(): void
    {
        $contacts = [5, 6];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturn(false);

        $this->contactModelMock->expects($this->never())
            ->method('getContactChannels');

        $this->actionModel->update($contacts, [], [], '');
    }

    public function testSubscribeContactToEmailChannel(): void
    {
        $contacts           = [5];
        $subscribedChannels = ['email', 'sms']; // Subscribe contact to these channels

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        // Contact is already subscribed to the SMS channel but not to email
        $this->contactModelMock->expects($this->once())
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

        $this->contactModelMock->expects($this->once())
            ->method('getPreferenceChannels')
            ->willReturn(['Email' => 'email', 'Text Message' => 'sms']);

        $this->doNotContactMock->expects($this->never())
            ->method('addDncForContact');

        $this->actionModel->update($contacts, $subscribedChannels);
    }

    public function testSubscribeContactWhoUnsubscribedToEmailChannel(): void
    {
        $contacts           = [5];
        $subscribedChannels = ['email', 'sms']; // Subscribe contact to these channels

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        // Contact is already subscribed to the SMS channel but not to email
        $this->contactModelMock->expects($this->once())
            ->method('getContactChannels')
            ->with($this->contactMock5)
            ->willReturn(['sms' => 'sms']);

        $this->doNotContactMock->expects($this->once())
            ->method('isContactable')
            ->with($this->contactMock5, 'email')
            ->willReturn(DNC::UNSUBSCRIBED);

        $this->doNotContactMock->expects($this->never())
            ->method('removeDncForContact');

        $this->contactModelMock->expects($this->once())
            ->method('getPreferenceChannels')
            ->willReturn(['Email' => 'email', 'Text Message' => 'sms']);

        $this->doNotContactMock->expects($this->never())
            ->method('addDncForContact');

        $this->actionModel->update($contacts, $subscribedChannels);
    }

    public function testUnsubscribeContactFromSmsChannel(): void
    {
        $contacts           = [5];
        $subscribedChannels = []; // Unsubscribe contact from missing

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        $this->contactModelMock->expects($this->once())
            ->method('getContactChannels')
            ->with($this->contactMock5)
            ->willReturn(['sms' => 'sms']);

        $this->doNotContactMock->expects($this->never())
            ->method('isContactable');

        $this->contactModelMock->expects($this->once())
            ->method('getPreferenceChannels')
            ->willReturn(['Email' => 'email', 'Text Message' => 'sms']);

        $this->doNotContactMock->expects($this->exactly(2))
            ->method('addDncForContact')
            ->withConsecutive(
                [5, 'email', DNC::MANUAL],
                [5, 'sms', DNC::MANUAL]
            );

        $this->actionModel->update($contacts, $subscribedChannels);
    }
}
