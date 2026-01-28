<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\ValidationEvent;
use Mautic\FormBundle\EventListener\FormValidationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

#[\PHPUnit\Framework\Attributes\CoversClass(FormValidationSubscriber::class)]
final class FormValidationSubscriberTest extends TestCase
{
    private function getSubscriber(): FormValidationSubscriber
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'mautic.form.submission.checkboxgrp.minimum' => 'You must select at least %min% options.',
            'mautic.form.submission.checkboxgrp.maximum' => 'You cannot select more than %max% options.',
        ], 'en', 'validators');

        return new FormValidationSubscriber($translator, $this->createMock(CoreParametersHelper::class));
    }

    public function testFailsWhenBelowMinimum(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 2]);

        $event      = new ValidationEvent($field, ['a']);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You must select at least 2 options.', $event->getInvalidReason());
    }

    public function testFailsWhenNoSelectionsProvided(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 2]);

        $event      = new ValidationEvent($field, []);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You must select at least 2 options.', $event->getInvalidReason());
    }

    public function testFailsWhenValueIsNull(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 1]);

        $event      = new ValidationEvent($field, null);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You must select at least 1 options.', $event->getInvalidReason());
    }

    public function testFailsWhenAboveMaximum(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['maximum' => 3]);

        $event      = new ValidationEvent($field, ['a', 'b', 'c', 'd']);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You cannot select more than 3 options.', $event->getInvalidReason());
    }

    public function testValidWithinRange(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation(['minimum' => 1, 'maximum' => 3]);

        $event      = new ValidationEvent($field, ['a', 'b']);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

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

        $event      = new ValidationEvent($field, ['a']);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

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

        $event      = new ValidationEvent($field, ['a', 'b', 'c']);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('Custom maximum message', $event->getInvalidReason());
    }

    public function testFallsBackToDefaultMessageWhenCustomMessageEmpty(): void
    {
        $field = new Field();
        $field->setType('checkboxgrp');
        $field->setValidation([
            'minimum'     => 2,
            'min_message' => '',
        ]);

        $event      = new ValidationEvent($field, ['a']);
        $subscriber = $this->getSubscriber();
        $subscriber->onFormValidate($event);

        $this->assertFalse($event->isValid());
        $this->assertSame('You must select at least 2 options.', $event->getInvalidReason());
    }
}
