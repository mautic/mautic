<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'cat_in_page_url',
            'yesno_button_group',
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
            'textarea',
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

        $builder->add(
            'google_analytics_add_to_email_preview',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.google.analytics.add.to.email.preview',
                'data'  => (bool) $options['data']['google_analytics_add_to_email_preview'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pageconfig';
    }
}
