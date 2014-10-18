<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormType
 *
 * @package Mautic\ChatBundle\Form\Type
 */
class ChannelType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('name', 'text', array(
            'label'      => 'mautic.chat.channel.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'      => 'form-control',
                'maxlength' => 25,
                'preaddon'   => 'symbol-hashtag'
            )
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.chat.channel.form.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'     => 'form-control',
                'maxlength' => 150
            ),
            'required'   => false
        ));


        /*
        $builder->add('isPrivate', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
                    'empty_value'   => false,
            'required'      => false
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.chat.channel.form.isprivate',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('privateUsers', 'collection', array(
            'allow_add'    => true,
            'by_reference' => false,
            'prototype'    => true
        ));
        */

        $builder->add('buttons', 'form_buttons', array(
            'apply_text'      => false,
            'save_text'       => 'mautic.core.form.save',
            'container_class' => 'chat-channel-buttons'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chatchannel';
    }
}