<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Form\Type\FormFieldEmailType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormFieldEmailTypeTest extends TestCase
{
    /**
     * @var CoreParametersHelper&MockObject
     */
    private CoreParametersHelper $coreParametersHelper;

    /**
     * @var FormBuilderInterface<string|FormBuilderInterface>&MockObject
     */
    private FormBuilderInterface $formBuilder;

    private FormFieldEmailType $formType;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(function (string $id): string {
                return match ($id) {
                    'mautic.form.submission.email.donotsubmit.invalid'    => 'Invalid email domain',
                    'mautic.form.submission.email.freeproviders.invalid'  => 'Invalid free email provider',
                    default                                               => $id,
                };
            });

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->formBuilder          = $this->createMock(FormBuilderInterface::class);
        $this->formType             = new FormFieldEmailType($translator, $this->coreParametersHelper);
    }

    public function testDomainFilterIsEnabledByDefaultWhenDenyListExists(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_submit_emails')
            ->willReturn(['*@google.com']);

        $this->assertDonotSubmitDefault([], true);
    }

    public function testDomainFilterKeepsSavedValueWhenDenyListIsEmpty(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_submit_emails')
            ->willReturn([]);

        $this->assertDonotSubmitDefault(['donotsubmit' => false], false);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertDonotSubmitDefault(array $data, bool $expectedDefault): void
    {
        $matcher = $this->exactly(4);

        $this->formBuilder->expects($matcher)
            ->method('add')
            ->willReturnCallback(function (...$parameters) use ($matcher, $expectedDefault) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('donotsubmit', $parameters[0]);
                    $this->assertSame(YesNoButtonGroupType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'mautic.form.field.type.donotsubmit',
                        'data'  => $expectedDefault,
                    ], $parameters[2]);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('donotsubmit_validationmsg', $parameters[0]);
                    $this->assertSame(TextType::class, $parameters[1]);
                    $this->assertSame('Invalid email domain', $parameters[2]['data']);
                }

                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('blockfreeemail', $parameters[0]);
                    $this->assertSame(YesNoButtonGroupType::class, $parameters[1]);
                }

                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('blockfreeemail_validationmsg', $parameters[0]);
                    $this->assertSame(TextType::class, $parameters[1]);
                }

                return $this->formBuilder;
            });

        $this->formType->buildForm($this->formBuilder, ['data' => $data]);
    }
}
