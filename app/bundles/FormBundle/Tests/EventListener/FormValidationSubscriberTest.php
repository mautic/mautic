<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\EventListener\FormValidationSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(FormValidationSubscriber::class)]
final class FormValidationSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private const MINIMUM_TWO_OPTIONS_MESSAGE = 'You must select at least 2 options.';

    private MockObject&TranslatorInterface $translator;

    private MockObject&CoreParametersHelper $coreParametersHelper;

    private FormValidationSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->subscriber = new FormValidationSubscriber(
            $this->translator,
            $this->coreParametersHelper,
        );
    }

    public function testFailsWhenBelowMinimum(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.checkboxgrp.minimum', ['%min%' => 2], 'validators')
            ->willReturn(self::MINIMUM_TWO_OPTIONS_MESSAGE);

        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 2]);

        $event = new ValidationEvent($field, ['a']);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame(self::MINIMUM_TWO_OPTIONS_MESSAGE, $event->getInvalidReason());
    }

    public function testFailsWhenNoSelectionsProvided(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.checkboxgrp.minimum', ['%min%' => 2], 'validators')
            ->willReturn(self::MINIMUM_TWO_OPTIONS_MESSAGE);

        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 2]);

        $event = new ValidationEvent($field, []);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame(self::MINIMUM_TWO_OPTIONS_MESSAGE, $event->getInvalidReason());
    }

    public function testFailsWhenValueIsNull(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.checkboxgrp.minimum', ['%min%' => 1], 'validators')
            ->willReturn('You must select at least 1 options.');

        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 1]);

        $event = new ValidationEvent($field, null);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You must select at least 1 options.', $event->getInvalidReason());
    }

    public function testFailsWhenAboveMaximum(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.checkboxgrp.maximum', ['%max%' => 3], 'validators')
            ->willReturn('You cannot select more than 3 options.');

        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['maximum' => 3]);

        $event = new ValidationEvent($field, ['a', 'b', 'c', 'd']);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You cannot select more than 3 options.', $event->getInvalidReason());
    }

    public function testValidWithinRange(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 1, 'maximum' => 3]);

        $event = new ValidationEvent($field, ['a', 'b']);
        $this->subscriber->onFormValidate($event);

        $this->assertTrue($event->isValid());
    }

    public function testUsesCustomMinimumMessage(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation([
            'minimum'     => 2,
            'min_message' => 'Custom minimum message',
        ]);

        $event = new ValidationEvent($field, ['a']);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Custom minimum message', $event->getInvalidReason());
    }

    public function testUsesCustomMaximumMessage(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation([
            'maximum'     => 2,
            'max_message' => 'Custom maximum message',
        ]);

        $event = new ValidationEvent($field, ['a', 'b', 'c']);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Custom maximum message', $event->getInvalidReason());
    }

    public function testFallsBackToDefaultMessageWhenCustomMessageEmpty(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.checkboxgrp.minimum', ['%min%' => 2], 'validators')
            ->willReturn(self::MINIMUM_TWO_OPTIONS_MESSAGE);

        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation([
            'minimum'     => 2,
            'min_message' => '',
        ]);

        $event = new ValidationEvent($field, ['a']);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame(self::MINIMUM_TWO_OPTIONS_MESSAGE, $event->getInvalidReason());
    }

    public function testEmailDonotSubmitDomainPatternTriggersFailure(): void
    {
        $field = new Field();
        $field->setType('email');
        $field->setValidation([
            'donotsubmit'               => 1,
            'donotsubmit_validationmsg' => 'Cannot be sent with this email',
        ]);

        $email = 'user@blocked.com';

        $this->coreParametersHelper
            ->method('get')
            ->willReturnCallback(fn (string $key): array => match ($key) {
                'do_not_submit_emails'         => ['*@blocked.com'],
                'blocked_free_email_providers' => [],
                default                        => [],
            });

        $event = new ValidationEvent($field, $email);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Cannot be sent with this email', $event->getInvalidReason());
    }

    public function testEmailBlockedFreeProviderTriggersFailure(): void
    {
        $field = new Field();
        $field->setType('email');
        $field->setValidation([
            'blockfreeemail'               => 1,
            'blockfreeemail_validationmsg' => 'Blocked free email provider',
        ]);

        $email = 'anyone@example.com';

        $this->coreParametersHelper
            ->method('get')
            ->willReturnCallback(fn (string $key): array => match ($key) {
                'do_not_submit_emails'         => [],
                'blocked_free_email_providers' => ['example.com'],
                default                        => [],
            });

        $event = new ValidationEvent($field, $email);
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Blocked free email provider', $event->getInvalidReason());
    }

    public function testCountryPhoneValidationFailsWithDefaultMessage(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.phone.invalid_country', ['%country%' => 'United States'], 'validators')
            ->willReturn('Please enter a valid United States phone number.');

        $field = new Field();
        $field->setType('tel');
        $field->setValidation(['country' => 'US']);

        $event = new ValidationEvent($field, '+5511999999999');
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Please enter a valid United States phone number.', $event->getInvalidReason());
    }

    public function testCountryPhoneValidationUsesCustomMessage(): void
    {
        $field = new Field();
        $field->setType('tel');
        $field->setValidation([
            'country'               => 'US',
            'country_validationmsg' => 'Use a US phone number.',
        ]);

        $event = new ValidationEvent($field, '+5511999999999');
        $this->subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Use a US phone number.', $event->getInvalidReason());
    }

    public function testCountryPhoneValidationAllowsMatchingCountry(): void
    {
        $field = new Field();
        $field->setType('tel');
        $field->setValidation(['country' => 'US']);

        $event = new ValidationEvent($field, '+12025550123');
        $this->subscriber->onFormValidate($event);

        $this->assertTrue($event->isValid());
    }

    public function testCountryPhoneValidationSkippedForUnknownCountry(): void
    {
        $field = new Field();
        $field->setType('tel');
        // A legacy/garbage value that is not a valid ISO region code must NOT
        // block the submission (and must never crash building the message).
        $field->setValidation(['country' => 'Atlantis']);

        $event = new ValidationEvent($field, '+5511999999999');
        $this->subscriber->onFormValidate($event);

        $this->assertTrue($event->isValid());
    }

    public function testCountryPhoneValidationFallsThroughToInternational(): void
    {
        $this->translator
            ->method('trans')
            ->with('mautic.form.submission.phone.invalid', [], 'validators')
            ->willReturn('Invalid international phone number.');

        $field = new Field();
        $field->setType('tel');
        // Valid for the country, so country validation passes; the international
        // validator then runs on the same value and must also accept it.
        $field->setValidation(['country' => 'US', 'international' => 1]);

        $event = new ValidationEvent($field, '+12025550123');
        $this->subscriber->onFormValidate($event);

        $this->assertTrue($event->isValid());
    }
}
