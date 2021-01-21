<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\SelectType;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Form\Type\EmailPreviewSettingsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailPreviewSettingsTypeTest extends TestCase
{
    /**
     * @var EmailPreviewSettingsType|MockObject
     */
    private $form;

    protected function setUp()
    {
        $this->form = new EmailPreviewSettingsType();

        parent::setUp();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(
                [
                    'emailId'      => null,
                    'translations' => null,
                    'variants'     => null,
                ]
            );

        $this->form->configureOptions($resolver);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame('email_preview_settings', $this->form->getBlockPrefix());
    }

    public function testBuildFormWithTranslationAndVariantFieldNotAvailable(): void
    {
        $emailId = 1;
        $options = [
            'emailId'      => $emailId,
            'translations' => [
                'children' => [],
            ],
            'variants'     => [
                'children' => [],
            ],
        ];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->withConsecutive(
                [
                    'contact',
                    SelectType::class,
                    [
                        'attr' => [
                            'onChange'         => "Mautic.emailPreview.regenerateUrl({$emailId})",
                            'data-placeholder' => 'Choose ...',
                        ],
                    ],
                ]
            );

        $this->form->buildForm($builder, $options);
    }

    public function testBuildFormWithTranslationAndVariantFieldAvailable(): void
    {
        $parentEmailId = 1;
        $parentEmail   = new Email();
        $parentEmail->setId($parentEmailId);
        $parentEmail->setName('Parent');
        $parentEmail->setLanguage('en');

        $translationEmail1 = new Email();
        $translationEmail1->setId(2);
        $translationEmail1->setName('Translation 1');
        $translationEmail1->setLanguage('cs_CZ');

        $translationEmail2 = new Email();
        $translationEmail2->setId(3);
        $translationEmail2->setName('Translation 2');
        $translationEmail2->setLanguage('dz_BT');

        $expectedTranslations = [
            'Parent - English'                  => 1,
            'Translation 1 - Czech (Czechia)'   => 2,
            'Translation 2 - Dzongkha (Bhutan)' => 3,
        ];

        $variantEmail1 = new Email();
        $variantEmail1->setId(2);
        $variantEmail1->setName('Variant 1');

        $variantEmail2 = new Email();
        $variantEmail2->setId(3);
        $variantEmail2->setName('Variant 2');

        $expectedVariants = [
            'Parent - ID 1'    => 1,
            'Variant 1 - ID 2' => 2,
            'Variant 2 - ID 3' => 3,
        ];

        $options = [
            'emailId'      => $parentEmailId,
            'translations' => [
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

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    'translation',
                    ChoiceType::class,
                    [
                        'choices' => $expectedTranslations,
                        'attr'    => [
                            'onChange' => "Mautic.emailPreview.regenerateUrl({$parentEmailId})",
                        ],
                        'placeholder' => 'Choose ...',
                    ],
                ],
                [
                    'variant',
                    ChoiceType::class,
                    [
                        'choices' => $expectedVariants,
                        'attr'    => [
                            'onChange' => "Mautic.emailPreview.regenerateUrl({$parentEmailId})",
                        ],
                        'placeholder' => 'Choose ...',
                    ],
                ],
                [
                    'contact',
                    SelectType::class,
                    [
                        'attr' => [
                            'onChange' => "Mautic.emailPreview.regenerateUrl({$parentEmailId})",
                        ],
                        'placeholder' => 'Choose ...',
                    ],
                ]
            );

        $this->form->buildForm($builder, $options);
    }
}
