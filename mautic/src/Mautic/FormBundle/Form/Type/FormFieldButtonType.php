<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormFieldButtonType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class FormFieldButtonType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice', array(
            'choices'      => array(
                'submit'     => 'mautic.form.field.form.button.submit',
                'reset'      => 'mautic.form.field.form.button.reset'
            ),
            'label'        => 'mautic.form.field.form.property_buttontype',
            'label_attr'   => array('class' => 'control-label'),
            'required' => false,
            'empty_value' => false,
            'attr'     => array(
                'class'         => 'form-control'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "formfield_button";
    }
}