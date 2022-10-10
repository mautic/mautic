<?php

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'csv_always_enclose',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.config.tab.form.csv_always_enclose',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.tab.form.csv_always_enclose.tooltip',
                ],
                'data'       => isset($options['data']['csv_always_enclose']) ? (bool) $options['data']['csv_always_enclose'] : false,
            ]
        );

        $builder->add(
            'form_results_data_sources',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.config.tab.form.form_results_data_sources',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.tab.form.form_results_data_sources.tooltip',
                ],
                'data'       => isset($options['data']['form_results_data_sources']) && (bool) $options['data']['form_results_data_sources'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'reportconfig';
    }
}
