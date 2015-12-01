<?php

/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticAnalyticsTaggingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class ConfigType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('utm_source', 'text', array(
            'label' => 'mautic.analytics.tagging.utm_source',
            'label_attr' => array('class' => 'control-label'),
            'attr' => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.analytics.tagging.utm_source.tooltip'
            ),
        ));
        $builder->add('utm_medium', 'text', array(
            'label' => 'mautic.analytics.tagging.utm_medium',
            'label_attr' => array('class' => 'control-label'),
            'attr' => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.analytics.tagging.utm_medium.tooltip'
            ),
        ));

        $builder->add('utm_campaign', 'choice', array(
            'choices' => array(
                'name' => 'mautic.analytics.tagging.utm_campaign.name',
                'subject' => 'mautic.analytics.tagging.utm_campaign.subject'
            ),
            'label' => 'mautic.analytics.tagging.utm_campaign',
            'attr' => array(
                'class' => 'form-control'
            ),
            'empty_value' => false,
            'constraints' => array(
                new NotBlank(
                        array(
                    'message' => 'mautic.core.value.required'
                        )
                )
            )
        ));

        $builder->add('remove_accents', 'yesno_button_group', array(
            'label' => 'mautic.analytics.tagging.remove_accents',
            'data' => (bool) $options['data']['remove_accents'],
            'attr' => array(
                'tooltip' => 'mautic.analytics.tagging.remove_accents.tooltip'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'tagging';
    }

}
