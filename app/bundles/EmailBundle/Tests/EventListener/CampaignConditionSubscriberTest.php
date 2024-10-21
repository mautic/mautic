<?php

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\EmailBundle\EventListener\CampaignConditionSubscriber;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CampaignConditionSubscriberTest extends TestCase
{
    /**
     * @var MockObject&EmailValidator
     */
    private MockObject $validator;

    private CampaignConditionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock EmailValidator (not the real object)
        $this->validator = $this->createMock(EmailValidator::class);

        // Initialize the CampaignConditionSubscriber with the mock validator
        $this->subscriber = new CampaignConditionSubscriber($this->validator);
    }

    public function testOnCampaignTriggerConditionReturnsFalseForNullEmail(): void
    {
        // Create a Lead object and set the email to null
        $lead = new Lead();
        $lead->setEmail(null);

        // Expect the validate method to throw an UnexpectedValueException for the null email
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(null, true)
            ->willThrowException(new UnexpectedValueException(null, 'string'));

        // Prepare the CampaignExecutionEvent with the lead and required event details
        $eventArgs = [
            'lead'            => $lead,
            'event'           => [
                'type' => 'email.validate.address',
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        // @phpstan-ignore-next-line (CampaignExecutionEvent is deprecated but needed for this test)
        $event = new CampaignExecutionEvent($eventArgs, true);

        // Call the onCampaignTriggerCondition method
        $this->subscriber->onCampaignTriggerCondition($event);

        // Assert that the result is false due to the exception
        $this->assertFalse($event->getResult());
    }

    public function testOnCampaignTriggerConditionReturnsFalseForInvalidEmail(): void
    {
        // Create a Lead object and set an invalid email
        $lead = new Lead();
        $lead->setEmail('invalid-email');

        // Expect the validate method to throw an InvalidEmailException for the invalid email
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($lead->getEmail(), true)
            ->willThrowException(new InvalidEmailException($lead->getEmail(), 'Invalid email format'));

        // Prepare the CampaignExecutionEvent with the lead and required event details
        $eventArgs = [
            'lead'            => $lead,
            'event'           => [
                'type' => 'email.validate.address',
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        // @phpstan-ignore-next-line (CampaignExecutionEvent is deprecated but needed for this test)
        $event = new CampaignExecutionEvent($eventArgs, true);

        // Call the onCampaignTriggerCondition method
        $this->subscriber->onCampaignTriggerCondition($event);

        // Assert that the result is false due to the exception
        $this->assertFalse($event->getResult());
    }

    public function testOnCampaignTriggerConditionReturnsTrueForValidEmail(): void
    {
        // Create a Lead object and set a valid email
        $lead = new Lead();
        $lead->setEmail('john.doe@example.com');

        // Expect the validate method to validate the email without throwing any exceptions
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($lead->getEmail(), true)
            ->willReturnCallback(function () {
                // Do nothing, as the method is void
            });

        // Prepare the CampaignExecutionEvent with the lead and required event details
        $eventArgs = [
            'lead'            => $lead,
            'event'           => [
                'type' => 'email.validate.address',
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        // @phpstan-ignore-next-line (CampaignExecutionEvent is deprecated but needed for this test)
        $event = new CampaignExecutionEvent($eventArgs, true);

        // Call the onCampaignTriggerCondition method
        $this->subscriber->onCampaignTriggerCondition($event);

        // Assert that the result is true for a valid email
        $this->assertTrue($event->getResult());
    }
}
