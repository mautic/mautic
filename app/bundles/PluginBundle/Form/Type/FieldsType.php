<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FieldsType.
 */
class FieldsType extends AbstractType
{
    use FieldsTypeTrait;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildFormFields($builder, $options, $options['integration_fields'], $options['lead_fields']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['integration_fields', 'lead_fields', 'integration', 'totalFields', 'page', 'fixedPageNum']);
        $resolver->setDefined(['update_mautic']);
        $resolver->setDefaults(
            [
                'special_instructions' => '',
                'alert_type'           => '',
                'allow_extra_fields'   => true,
                'enable_data_priority' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'integration_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['specialInstructions'] = $options['special_instructions'];
        $view->vars['alertType']           = $options['alert_type'];
        $view->vars['integration']         = $options['integration'];
        $view->vars['totalFields']         = $options['totalFields'];
        $view->vars['page']                = $options['page'];
        $view->vars['fixedPageNum']        = $options['fixedPageNum'];
    }
}
