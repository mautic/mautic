<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class NotificationSendType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class NotificationSendType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('notification_message_headings', 'textarea', array(
            'label_attr' => array('class' => 'control-label'),
            'label' => 'mautic.notification.headings',
            'required' => true,
            'attr' => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.notification.headings.tooltip'
            )
        ));

        $builder->add('notification_message_template', 'textarea', array(
            'label_attr' => array('class' => 'control-label'),
            'label' => 'mautic.notification.text',
            'required' => true,
            'attr' => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.notification.text.tooltip'
            )
        ));

        $builder->add('notification_link', 'url', array(
            'label_attr' => array('class' => 'control-label'),
            'label' => 'mautic.notification.link',
            'required' => false,
            'attr' => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.notification.link.tooltip',
                'placeholder' => 'mautic.notification.link.placeholder'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "notificationsend_list";
    }
}