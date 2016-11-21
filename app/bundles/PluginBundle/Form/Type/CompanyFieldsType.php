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

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SocialMediaServiceType.
 */
class CompanyFieldsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['integration_company_fields'] as $field => $details) {
            $label = (is_array($details)) ? $details['label'] : $details;
            $field = InputHelper::alphanum($field, false, '_');

            $builder->add($field, 'choice', [
                'choices'    => $options['company_fields'],
                'label'      => $label,
                'required'   => (is_array($details) && isset($details['required'])) ? $details['required'] : false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control', 'data-placeholder' => ' '],
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['integration_company_fields', 'company_fields']);
        $resolver->setDefaults(
            [
                'special_instructions' => '',
                'alert_type'           => '',
                'allow_extra_fields'   => true,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'integration_company_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['specialInstructions'] = $options['special_instructions'];
        $view->vars['alertType']           = $options['alert_type'];
    }
}
