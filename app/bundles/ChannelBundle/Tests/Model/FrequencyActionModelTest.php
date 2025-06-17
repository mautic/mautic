<?php

namespace Mautic\ChannelBundle\Tests\Model;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Mautic\ChannelBundle\Model\FrequencyActionModel;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;

class FrequencyActionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Lead
     */
    private MockObject $contactMock5;

    /**
     * @var MockObject|LeadModel
     */
    private MockObject $contactModelMock;

    /**
     * @var MockObject|FrequencyRuleRepository
     */
    private MockObject $frequencyRepoMock;

    /**
     * @var MockObject|FrequencyRule
     */
    private MockObject $frequencyRuleEmailMock;

    /**
     * @var MockObject|FrequencyRule
     */
    private MockObject $frequencyRuleSmsMock;

    private FrequencyActionModel $actionModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactMock5           = $this->createMock(Lead::class);
        $this->contactModelMock       = $this->createMock(LeadModel::class);
        $this->frequencyRepoMock      = $this->createMock(FrequencyRuleRepository::class);
        $this->frequencyRuleEmailMock = $this->createMock(FrequencyRule::class);
        $this->frequencyRuleSmsMock   = $this->createMock(FrequencyRule::class);
        $collectionMock               = $this->createMock(AbstractLazyCollection::class);
        $this->actionModel            = new FrequencyActionModel(
            $this->contactModelMock,
            $this->frequencyRepoMock
        );

        $collectionMock->method('toArray')
            ->willReturn([
                'email' => $this->frequencyRuleEmailMock,
                'sms'   => $this->frequencyRuleSmsMock,
            ]);

        $this->contactMock5->method('getFrequencyRules')->willReturn($collectionMock);
    }

    public function testUpdateWhenEntityAccess(): void
    {
        $contacts = [5];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(false);

        $this->contactModelMock->expects($this->never())
            ->method('getPreferenceChannels');

        $this->actionModel->update($contacts, [], '');
    }

    public function testUpdate(): void
    {
        $contacts = [5];
        $params   = [
            'subscribed_channels'            => ['email', 'sms'],
            'frequency_number_email'         => '2',
            'frequency_time_email'           => 'WEEK',
            'preferred_channel'              => 'email',
            'contact_pause_start_date_email' => '2018-05-13',
            'contact_pause_end_date_email'   => '2018-05-26',
            'frequency_number_sms'           => '',
            'frequency_time_sms'             => '',
            'contact_pause_start_date_sms'   => '',
            'contact_pause_end_date_sms'     => '',
        ];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        $this->contactModelMock->expects($this->once())
            ->method('getPreferenceChannels')
            ->willReturn([
                'Email'        => 'email',
                'Text Message' => 'sms',
            ]);

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setChannel')
            ->with('email');

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setLead')
            ->with($this->contactMock5);

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setDateAdded');

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setFrequencyNumber')
            ->with('2');

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setFrequencyTime')
            ->with('WEEK');

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setPauseFromDate')
            ->with(new \DateTime('2018-05-13T00:00:00.000000+0000'));

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setPauseToDate')
            ->with(new \DateTime('2018-05-26T00:00:00.000000+0000'));

        $this->frequencyRuleEmailMock->expects($this->once())
            ->method('setPreferredChannel')
            ->with(true);
        $matcher = $this->exactly(2);

        $this->contactMock5->expects($matcher)
            ->method('addFrequencyRule')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals($this->frequencyRuleEmailMock, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertEquals($this->frequencyRuleEmailMock, $parameters[0]);
                }
            });
        $matcher = $this->exactly(2);

        $this->frequencyRepoMock->expects($matcher)
            ->method('saveEntity')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->frequencyRuleEmailMock, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->frequencyRuleSmsMock, $parameters[0]);
                }
            });

        $this->frequencyRuleSmsMock->expects($this->once())
            ->method('setChannel')
            ->with('sms');

        $this->frequencyRuleSmsMock->expects($this->once())
            ->method('setLead')
            ->with($this->contactMock5);

        $this->frequencyRuleSmsMock->expects($this->once())
            ->method('setDateAdded');

        $this->frequencyRuleSmsMock->expects($this->once())
            ->method('setFrequencyNumber')
            ->with(null);

        $this->frequencyRuleSmsMock->expects($this->once())
            ->method('setFrequencyTime')
            ->with(null);

        $this->frequencyRuleSmsMock->expects($this->never())
            ->method('setPauseFromDate');

        $this->frequencyRuleSmsMock->expects($this->never())
            ->method('setPauseToDate');

        $this->frequencyRuleSmsMock->expects($this->once())
            ->method('setPreferredChannel')
            ->with(false);

        $this->actionModel->update($contacts, $params, 'email');
    }
}
