<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class WebPushType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class WebPushType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('webpush_message_template', 'textarea', array(
            'label_attr' => array('class' => 'control-label'),
            'label' => 'mautic.webpush.text',
            'required' => true,
            'attr' => array(
                'class' => 'form-control',
                'placeholder' => 'mautic.webpush.placeholder'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "webpush";
    }
}