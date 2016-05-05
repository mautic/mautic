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
class MergeType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'lead_to_merge',
            'choice',
            array(
                'choices'     => $options['leads'],
                'label'       => 'mautic.lead.merge.select',
                'label_attr'  => array('class' => 'control-label'),
                'multiple'    => false,
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

        $builder->add(
            'buttons',
            'form_buttons',
            array(
                'apply_text' => false,
                'save_text'  => 'mautic.lead.merge',
                'save_icon'  => 'fa fa-user'
            )
        );

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('leads'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "lead_merge";
    }
}