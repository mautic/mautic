<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Model;

use Mautic\ChannelBundle\Model\FrequencyActionModel;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class FrequencyActionModelTest extends \PHPUnit\Framework\TestCase
{
    private Lead $contact;

    /**
     * @var MockObject&LeadModel
     */
    private MockObject $contactModelMock;

    /**
     * @var MockObject&FrequencyRuleRepository
     */
    private MockObject $frequencyRepoMock;

    private FrequencyRule $frequencyRuleEmail;

    private FrequencyRule $frequencyRuleSms;

    private FrequencyActionModel $actionModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contact            = new Lead();
        $this->contactModelMock   = $this->createMock(LeadModel::class);
        $this->frequencyRepoMock  = $this->createMock(FrequencyRuleRepository::class);
        $this->frequencyRuleEmail = new FrequencyRule();
        $this->frequencyRuleSms   = new FrequencyRule();
        $this->actionModel        = new FrequencyActionModel(
            $this->contactModelMock,
            $this->frequencyRepoMock
        );

        $this->contact->setFrequencyRules([
            'email' => $this->frequencyRuleEmail,
            'sms'   => $this->frequencyRuleSms,
        ]);
    }

    public function testUpdateWhenEntityAccess(): void
    {
        $contacts = [5];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contact]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contact)
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
            ->willReturn([$this->contact]);

        $this->contactModelMock->expects($this->once())
            ->method('canEditContact')
            ->with($this->contact)
            ->willReturn(true);

        $this->contactModelMock->expects($this->once())
            ->method('getPreferenceChannels')
            ->willReturn([
                'Email'        => 'email',
                'Text Message' => 'sms',
            ]);
        $matcher = $this->exactly(2);

        $this->frequencyRepoMock->expects($matcher)
            ->method('saveEntity')->willReturnCallback(function (...$parameters) use ($matcher): void {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->frequencyRuleEmail, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->frequencyRuleSms, $parameters[0]);
                }
            });

        $this->actionModel->update($contacts, $params, 'email');

        $this->assertSame('email', $this->frequencyRuleEmail->getChannel());
        $this->assertSame($this->contact, $this->frequencyRuleEmail->getLead());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->frequencyRuleEmail->getDateAdded());
        $this->assertSame('2', $this->frequencyRuleEmail->getFrequencyNumber());
        $this->assertSame('WEEK', $this->frequencyRuleEmail->getFrequencyTime());
        $this->assertEquals(new \DateTime('2018-05-13T00:00:00.000000+0000'), $this->frequencyRuleEmail->getPauseFromDate());
        $this->assertEquals(new \DateTime('2018-05-26T00:00:00.000000+0000'), $this->frequencyRuleEmail->getPauseToDate());
        $this->assertTrue($this->frequencyRuleEmail->getPreferredChannel());

        $this->assertSame('sms', $this->frequencyRuleSms->getChannel());
        $this->assertSame($this->contact, $this->frequencyRuleSms->getLead());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->frequencyRuleSms->getDateAdded());
        $this->assertNull($this->frequencyRuleSms->getFrequencyNumber());
        $this->assertNull($this->frequencyRuleSms->getFrequencyTime());
        $this->assertNull($this->frequencyRuleSms->getPauseFromDate());
        $this->assertNull($this->frequencyRuleSms->getPauseToDate());
        $this->assertFalse($this->frequencyRuleSms->getPreferredChannel());
    }
}
