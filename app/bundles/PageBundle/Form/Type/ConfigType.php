<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'cat_in_page_url',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.page.config.form.cat.in.url',
                'data'  => (bool) $options['data']['cat_in_page_url'],
                'attr'  => [
                    'tooltip' => 'mautic.page.config.form.cat.in.url.tooltip',
                ],
            ]
        );

        $builder->add(
            'google_analytics',
            TextareaType::class,
            [
                'label'      => 'mautic.page.config.form.google.analytics',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.config.form.google.analytics.tooltip',
                    'rows'    => 10,
                ],
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pageconfig';
    }
}
