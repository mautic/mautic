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
            'track_contact_by_ip',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.track_contact_by_ip',
                'data'  => (bool) $options['data']['track_contact_by_ip'],
                'attr'  => [
                    'tooltip' => 'mautic.page.config.form.track_contact_by_ip.tooltip',
                ],
            ]
        );

        $builder->add('track_by_fingerprint', 'yesno_button_group', [
            'label' => 'mautic.page.config.form.track.by.fingerprint',
            'data'  => (bool) $options['data']['track_by_fingerprint'],
            'attr'  => [
                'tooltip' => 'mautic.page.config.form.track.by.fingerprint.tooltip',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pageconfig';
    }
}
