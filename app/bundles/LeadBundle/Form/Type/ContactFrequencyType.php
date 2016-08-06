<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class MergeType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class ContactFrequencyType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'channels',
            'choice',
            array(
                'choices'     => array(
                    'sms' =>'SMS',
                    'email' => 'Email'
                ),
                'label'       => 'mautic.lead.contact.channels',
                'label_attr'  => array('class' => 'control-label'),
                'multiple'    => true,
                'empty_value' => '',
                'attr'        => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.lead.merge.select.modal.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );
        $builder->add('frequency_number','number',
            array(
                'precision'  => 0,
                'label'      => 'mautic.lead.list.frequency.number',
                'label_attr' => array('class' => 'control-label'),
                'required'   => false,
                'attr'       => array(
                    'class' => 'form-control frequency'
                )
            ));
        $builder->add('frequency_time','choice',
            array(
                'choices'    => array(
                    '1D' => 'day',
                    '1W' => 'week',
                    '1M' => 'month'
                ),
                'label'      => 'mautic.lead.list.frequency.times',
                'label_attr' => array('class' => 'control-label'),
                'required'   => false,
                'multiple'   => false,
                'attr'       => array(
                    'class' => 'form-control frequency'
                )
            ));

        $builder->add(
            'buttons',
            'form_buttons',
            array(
                'apply_text'     => false,
                'save_text'      => 'mautic.core.form.save',
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => array(
                    'data-dismiss' => 'modal'
                ),
                'attr'       => array(
                    'class' => 'modal-form-buttons'
                )
            )
        );

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "lead_contact_frequency_rules";
    }
}

