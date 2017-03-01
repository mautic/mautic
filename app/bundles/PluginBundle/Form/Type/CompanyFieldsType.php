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
        $index                    = 0;
        $integrationFields        = array_combine(array_keys($options['integration_company_fields']), array_keys($options['integration_company_fields']));
        $data                     = isset($options['data']) ? $options['data'] : [];
        $integrationFieldsOrdered = array_merge($data, $integrationFields);

        foreach ($integrationFieldsOrdered as $field => $details) {
            ++$index;
            $builder->add('i_'.$index, 'choice', [
                'choices'  => $integrationFieldsOrdered,
                'label'    => false,
                'required' => true,
                'data'     => isset($data[$field]) ? $field : '',
                'attr'     => ['class' => 'field-selector form-control', 'data-placeholder' => ' '],
                'disabled' => ($index > 1 && !isset($data[$field])) ? true : false,
            ]);
            if (isset($options['enable_data_priority']) and $options['enable_data_priority']) {
                $builder->add('update_mautic_company'.$index,
                    'button_group',
                    [
                        'choices'     => ['<btn class="btn-nospin fa fa-arrow-circle-left"></btn>', '<btn class="btn-nospin fa fa-arrow-circle-right"></btn>'],
                        'label'       => false,
                        'data'        => isset($options['update_mautic_company'][$field]) ? (bool) $options['update_mautic_company'][$field] : 1,
                        'empty_value' => false,
                        'attr'        => ['data-toggle' => 'tooltip', 'title' => 'mautic.plugin.direction.data.update'],
                        'disabled'    => ($index > 1 && !isset($data[$field])) ? true : false,
                    ]);
            }
            $builder->add('m_'.$index, 'choice', [
                'choices'    => $options['company_fields'],
                'label'      => false,
                'required'   => true,
                'data'       => isset($data[$field]) ? $data[$field] : '',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'field-selector form-control', 'data-placeholder' => ' '],
                'disabled'   => ($index > 1 && !isset($data[$field])) ? true : false,
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['integration_company_fields', 'company_fields', 'update_mautic_company']);
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
