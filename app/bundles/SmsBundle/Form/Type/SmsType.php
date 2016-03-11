<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SmsType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class SmsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('sms_message_template', 'textarea', array(
            'label_attr' => array('class' => 'control-label'),
            'label' => 'mautic.sms.text',
            'required' => true,
            'attr' => array(
                'class' => 'form-control',
                'placeholder' => 'mautic.sms.placeholder'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "sms";
    }
}