<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormFieldCaptchaType
 */
class FormFieldCaptchaType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('captcha', 'text', array(
            'label'      => 'mautic.form.field.form.property_captcha',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.form.field.help.captcha'
            ),
            'required'   => true,
            'constraints' => array(
                new NotBlank(
                    array('message' => 'mautic.form.field.property_captcha.notblank')
                )
            )
        ));

        $builder->add('placeholder', 'text', array(
            'label'      => 'mautic.form.field.form.property_placeholder',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('errorMessage', 'text', array(
            'label'      => 'mautic.form.field.form.property_captchaerror',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "formfield_captcha";
    }
}
