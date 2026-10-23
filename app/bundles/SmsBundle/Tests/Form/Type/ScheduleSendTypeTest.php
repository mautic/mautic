<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\SmsBundle\Form\Type\ScheduleSendType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class ScheduleSendTypeTest extends TypeTestCase
{
    /**
     * @return array<int, ValidatorExtension|PreloadedExtension>
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([
                new ScheduleSendType(),
                new ButtonGroupType(),
                new YesNoButtonGroupType(),
                new FormButtonsType(),
            ], []),
        ];
    }

    public function testInitialScheduleUsesScheduleButtonAndDynamicStopField(): void
    {
        $form = $this->createForm(false);

        $this->assertSame('mautic.sms.send.schedule', $this->getButtonLabel($form, 'save'));
        $this->assertFalse($form->get('buttons')->has('apply'));
        $this->assertSame('{"schedule_send_continueSending_1":"checked"}', $form->get('publishDown')->getConfig()->getOption('attr')['data-show-on']);
    }

    public function testExistingOneTimeScheduleUsesUpdateCancelAndCloseButtons(): void
    {
        $form = $this->createForm(true);

        $this->assertSame('mautic.sms.send.schedule.update', $this->getButtonLabel($form, 'save'));
        $this->assertSame('mautic.sms.send.schedule.cancel', $this->getButtonLabel($form, 'apply'));
        $this->assertSame('mautic.core.close', $this->getButtonLabel($form, 'cancel'));
    }

    public function testDisablingContinuingModeClearsSubmittedStopDate(): void
    {
        $form = $this->createForm(true);
        $form->submit([
            'publishUp'       => '2026-01-01 10:00',
            'continueSending' => '0',
            'publishDown'     => '2026-01-01 11:00',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame(0, $form->getData()['continueSending']);
        $this->assertNull($form->getData()['publishDown']);
    }

    public function testContinuingScheduleAcceptsAnOptionalStopDate(): void
    {
        $form = $this->createForm(false);
        $form->submit([
            'publishUp'       => '2026-01-01 10:00',
            'continueSending' => '1',
            'publishDown'     => '',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertNull($form->getData()['publishDown']);
    }

    public function testContinuingScheduleRejectsStopAtOrBeforeStart(): void
    {
        $form = $this->createForm(false);
        $form->submit([
            'publishUp'       => '2026-01-01 10:00',
            'continueSending' => '1',
            'publishDown'     => '2026-01-01 10:00',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('publishDown')->getErrors());
    }

    public function testStartDateIsRequired(): void
    {
        $form = $this->createForm(false);
        $form->submit([
            'publishUp'       => '',
            'continueSending' => '0',
            'publishDown'     => '',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('publishUp')->getErrors());
    }

    /**
     * @return FormInterface<array<string, mixed>>
     */
    private function createForm(bool $isScheduled): FormInterface
    {
        return $this->factory->create(ScheduleSendType::class, [
            'publishUp'       => null,
            'publishDown'     => null,
            'continueSending' => false,
        ], [
            'is_scheduled' => $isScheduled,
        ]);
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function getButtonLabel(FormInterface $form, string $button): string
    {
        return (string) $form->get('buttons')->get($button)->getConfig()->getOption('label');
    }
}
