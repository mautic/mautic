<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\Helper\PhoneCountryValidationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
final class FormFieldTelType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'country',
            ChoiceType::class,
            [
                'label'       => 'mautic.form.field.type.tel.country_validation',
                'choices'     => PhoneCountryValidationHelper::getCountries(),
                'placeholder' => 'mautic.core.none',
                'required'    => false,
                'data'        => $options['data']['country'] ?? null,
            ]
        );

        $builder->add(
            'international',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.field.type.tel.international',
                'data'  => $options['data']['international'] ?? false,
            ]
        );

        $builder->add(
            'international_validationmsg',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.validationmsg',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => $this->translator->trans('mautic.form.field.type.tel.international_validationmsg.tooltip'),
                    'data-show-on' => '{"formfield_validation_international_1": "checked"}',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'country_validationmsg',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.validationmsg',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'tooltip'     => $this->translator->trans('mautic.form.field.type.tel.country_validationmsg.tooltip'),
                    'placeholder' => $this->translator->trans('mautic.form.field.type.tel.country_validationmsg.placeholder'),
                ],
                'required'   => false,
            ]
        );
    }
}
