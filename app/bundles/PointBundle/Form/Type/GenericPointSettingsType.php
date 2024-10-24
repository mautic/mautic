<?php

namespace Mautic\PointBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * @extends AbstractType<array<mixed>>
 *
 * @deprecated To be removed in 6.0. No longer necessary as of https://github.com/mautic/mautic/pull/13868
 */
class GenericPointSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $default = (empty($options['data']['delta'])) ? 0 : (int) $options['data']['delta'];
        $builder->add(
            'delta',
            NumberType::class,
            [
                'label'      => 'mautic.point.action.delta',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.point.action.delta.help',
                ],
                'scale'       => 0,
                'data'        => $default,
                'constraints' => [
                    new NotEqualTo(
                        [
                            'value'   => '0',
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'genericpoint_settings';
    }
}
