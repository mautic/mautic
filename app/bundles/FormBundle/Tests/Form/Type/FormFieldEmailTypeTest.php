<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Form\Type\FormFieldEmailType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormFieldEmailTypeTest extends TypeTestCase
{
    private MockObject&TranslatorInterface $translator;

    private MockObject&CoreParametersHelper $coreParametersHelper;

    protected function setUp(): void
    {
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        parent::setUp();
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                ButtonGroupType::class      => new ButtonGroupType(),
                YesNoButtonGroupType::class => new YesNoButtonGroupType(),
                FormFieldEmailType::class   => new FormFieldEmailType($this->translator, $this->coreParametersHelper),
            ], []),
        ];
    }

    public function testDoNotSubmitDefaultsToTrueWhenConfiguredDomainsExist(): void
    {
        $this->coreParametersHelper
            ->method('get')
            ->with('do_not_submit_emails')
            ->willReturn(['blocked.com']);

        $form = $this->factory->create(FormFieldEmailType::class, [], [
            'data' => $this->getValidationData(),
        ]);

        self::assertTrue((bool) $form->get('donotsubmit')->getData());
    }

    public function testDoNotSubmitDefaultsToFalseWhenConfiguredDomainsDoNotExist(): void
    {
        $this->coreParametersHelper
            ->method('get')
            ->with('do_not_submit_emails')
            ->willReturn([]);

        $form = $this->factory->create(FormFieldEmailType::class, [], [
            'data' => $this->getValidationData(),
        ]);

        self::assertFalse((bool) $form->get('donotsubmit')->getData());
    }

    public function testDoNotSubmitPreservesSavedFalseWhenConfiguredDomainsExist(): void
    {
        $this->coreParametersHelper
            ->expects(self::never())
            ->method('get');

        $form = $this->factory->create(FormFieldEmailType::class, [], [
            'data' => $this->getValidationData([
                'donotsubmit' => false,
            ]),
        ]);

        self::assertFalse((bool) $form->get('donotsubmit')->getData());
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function getValidationData(array $data = []): array
    {
        return array_merge([
            'donotsubmit_validationmsg'    => 'Cannot be sent with this email',
            'blockfreeemail_validationmsg' => 'Please provide a business email address',
        ], $data);
    }
}
