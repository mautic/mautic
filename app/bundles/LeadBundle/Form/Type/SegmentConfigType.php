<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class SegmentConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface<FormBuilderInterface> $builder
     * @param mixed[]                                    $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'segment_rebuild_time_warning',
            NumberType::class,
            [
                'label'      => 'mautic.lead.list.form.config.segment_rebuild_time_warning',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.list.form.config.segment_rebuild_time_warning.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'segment_build_time_warning',
            NumberType::class,
            [
                'label'      => 'mautic.lead.list.form.config.segment_build_time_warning',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.list.form.config.segment_build_time_warning.tooltip',
                ],
                'required' => false,
            ]
        );
    }
}
