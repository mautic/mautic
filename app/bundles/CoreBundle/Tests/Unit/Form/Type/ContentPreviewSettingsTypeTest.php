<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\Type;

use Mautic\CoreBundle\Form\Type\ContentPreviewSettingsType;
use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentPreviewSettingsTypeTest extends TestCase
{
    private ContentPreviewSettingsType $form;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var CorePermissions&MockObject
     */
    private MockObject $security;

    /**
     * @var UserHelper&MockObject
     */
    private MockObject $userHelperMock;

    protected function setUp(): void
    {
        $this->translator     = $this->createMock(TranslatorInterface::class);
        $this->security       = $this->createMock(CorePermissions::class);
        $this->userHelperMock = $this->createMock(UserHelper::class);
        $this->form           = new ContentPreviewSettingsType($this->translator, $this->security, $this->userHelperMock);

        parent::setUp();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(
                [
                    'type'         => null,
                    'objectId'     => null,
                    'translations' => null,
                    'variants'     => null,
                ]
            );

        $resolver->expects(self::once())
            ->method('setRequired')
            ->with(['type', 'objectId']);

        $resolver->expects(self::once())
            ->method('addAllowedValues')
            ->with('type', [ContentPreviewSettingsType::TYPE_PAGE, ContentPreviewSettingsType::TYPE_EMAIL]);

        $resolver->expects(self::once())
            ->method('addAllowedTypes')
            ->with('objectId', 'int');

        $this->form->configureOptions($resolver);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame('content_preview_settings', $this->form->getBlockPrefix());
    }

    public function testBuildFormWithTranslationAndVariantFieldNotAvailable(): void
    {
        $objectId = 1;
        $options  = [
            'objectId'      => $objectId,
            'translations'  => [
                'children' => [],
            ],
            'variants'     => [
                'children' => [],
            ],
        ];

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.list.form.startTyping'],
                ['mautic.core.form.nomatches']
            )->willReturnOnConsecutiveCalls(
                'startTyping',
                'nomatches'
            );

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                $this->getContactFieldDefinition()
            );

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(true);
        $this->security->expects(self::never())
            ->method('hasEntityAccess');

        $this->form->buildForm($builder, $options);
    }

    public function testBuildFormWithTranslationAndVariantFieldNotAvailableAndNoAccessPermissions(): void
    {
        $objectId = 1;
        $userId   = 37;
        $options  = [
            'objectId'      => $objectId,
            'translations'  => [
                'children' => [],
            ],
            'variants'     => [
                'children' => [],
            ],
        ];

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(false);

        $userMock = $this->createMock(User::class);
        $userMock->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $this->userHelperMock->expects(self::once())
            ->method('getUser')
            ->willReturn($userMock);

        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with('lead:leads:viewown', 'lead:leads:viewother', $userId)
            ->willReturn(false);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())
            ->method('add');
        $this->form->buildForm($builder, $options);
    }

    public function testBuildFormWithTranslationAndVariantFieldNotAvailableAndAdminPermissions(): void
    {
        $objectId = 1;
        $options  = [
            'objectId'      => $objectId,
            'translations'  => [
                'children' => [],
            ],
            'variants'     => [
                'children' => [],
            ],
        ];

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(true);
        $this->security->expects(self::never())
            ->method('hasEntityAccess');

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.list.form.startTyping'],
                ['mautic.core.form.nomatches']
            )->willReturnOnConsecutiveCalls(
                'startTyping',
                'nomatches'
            );

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                $this->getContactFieldDefinition()
            );

        $this->form->buildForm($builder, $options);
    }

    public function testBuildFormWithTranslationAndVariantFieldNotAvailableAndEntityPermissions(): void
    {
        $userId   = 37;
        $objectId = 1;
        $options  = [
            'objectId'      => $objectId,
            'translations'  => [
                'children' => [],
            ],
            'variants'     => [
                'children' => [],
            ],
        ];

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(false);

        $userMock = $this->createMock(User::class);
        $userMock->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $this->userHelperMock->expects(self::once())
            ->method('getUser')
            ->willReturn($userMock);

        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with('lead:leads:viewown', 'lead:leads:viewother', $userId)
            ->willReturn(true);

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.list.form.startTyping'],
                ['mautic.core.form.nomatches']
            )->willReturnOnConsecutiveCalls(
                'startTyping',
                'nomatches'
            );

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                $this->getContactFieldDefinition()
            );

        $this->form->buildForm($builder, $options);
    }

    public function testBuildFormWithTranslationAndVariantFieldAvailable(): void
    {
        $parentEmailId = 1;
        $parentEmail   = $this->createEmail();
        $parentEmail->setId($parentEmailId); // @phpstan-ignore-line
        $parentEmail->setName('Parent');
        $parentEmail->setLanguage('en');

        $translationEmail1 = $this->createEmail();
        $translationEmail1->setId(2); // @phpstan-ignore-line
        $translationEmail1->setName('Translation 1');
        $translationEmail1->setLanguage('cs_CZ');

        $translationEmail2 = $this->createEmail();
        $translationEmail2->setId(3); // @phpstan-ignore-line
        $translationEmail2->setName('Translation 2');
        $translationEmail2->setLanguage('dz_BT');

        $expectedTranslationChoices = [
            'Parent - English - ID 1'                  => 1,
            'Translation 1 - Czech (Czechia) - ID 2'   => 2,
            'Translation 2 - Dzongkha (Bhutan) - ID 3' => 3,
        ];

        $variantEmail1 = $this->createEmail();
        $variantEmail1->setId(2); // @phpstan-ignore-line
        $variantEmail1->setName('Variant 1');

        $variantEmail2 = $this->createEmail();
        $variantEmail2->setId(3); // @phpstan-ignore-line
        $variantEmail2->setName('Variant 2');

        $expectedVariantChoices = [
            'Parent - ID 1'    => 1,
            'Variant 1 - ID 2' => 2,
            'Variant 2 - ID 3' => 3,
        ];

        $formOptions = [
            'objectId'      => $parentEmailId,
            'translations'  => [
                'parent'   => $parentEmail,
                'children' => [
                    $translationEmail1,
                    $translationEmail2,
                ],
            ],
            'variants' => [
                'parent'   => $parentEmail,
                'children' => [
                    $variantEmail1,
                    $variantEmail2,
                ],
            ],
        ];

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(true);
        $this->security->expects(self::never())
            ->method('hasEntityAccess');

        $this->translator->expects(self::exactly(4))
            ->method('trans')
            ->withConsecutive(
                ['mautic.core.form.chooseone'],
                ['mautic.core.form.chooseone'],
                ['mautic.lead.list.form.startTyping'],
                ['mautic.core.form.nomatches']
            )->willReturnOnConsecutiveCalls(
                'chooseone',
                'chooseone',
                'startTyping',
                'nomatches'
            );

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects(self::exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    'translation',
                    ChoiceType::class,
                    [
                        'choices' => $expectedTranslationChoices,
                        'attr'    => [
                            'onChange' => "Mautic.contentPreviewUrlGenerator.regenerateUrl({$parentEmailId}, this)",
                        ],
                        'placeholder'  => 'chooseone',
                        'data'         => (string) $parentEmailId,
                    ],
                ],
                [
                    'variant',
                    ChoiceType::class,
                    [
                        'choices' => $expectedVariantChoices,
                        'attr'    => [
                            'onChange' => "Mautic.contentPreviewUrlGenerator.regenerateUrl({$parentEmailId}, this)",
                        ],
                        'placeholder'  => 'chooseone',
                        'data'         => (string) $parentEmailId,
                    ],
                ],
                $this->getContactFieldDefinition()
            );

        $this->form->buildForm($formBuilder, $formOptions);
    }

    /**
     * @return array<mixed>
     */
    private function getContactFieldDefinition(): array
    {
        return [
            'contact',
            LookupType::class,
            [
                'attr' => [
                    'class'                   => 'form-control',
                    'data-callback'           => 'activatePreviewContactLookupField',
                    'data-toggle'             => 'field-lookup',
                    'data-lookup-callback'    => 'updatePreviewContactLookupListFilter',
                    'data-chosen-lookup'      => 'lead:contactList',
                    'placeholder'             => 'startTyping',
                    'data-no-record-message'  => 'nomatches',
                ],
            ],
        ];
    }

    private function createEmail(): Email
    {
        return new class extends Email {
            private int $id = 0;

            public function getId(): int
            {
                return $this->id;
            }

            public function setId(int $id): Email
            {
                $this->id = $id;

                return $this;
            }
        };
    }
}
