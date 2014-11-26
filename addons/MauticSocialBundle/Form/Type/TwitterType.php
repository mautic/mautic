<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class TwitterType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class TwitterType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('shareBtnMsg', 'spacer', array(
           'text' =>  'mautic.social.form.sharebutton'
        ));

        $builder->add('count', 'choice', array(
            'choices'     => array(
                'horizontal' => 'mautic.social.Twitter.share.layout.horizontal',
                'vertical'   => 'mautic.social.Twitter.share.layout.vertical',
                'none'       => 'mautic.social.Twitter.share.layout.none'
            ),
            'label'       => 'mautic.social.Twitter.share.layout',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class'   => 'form-control')
        ));

        $builder->add('text', 'text', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.social.Twitter.share.text',
            'required'      => false,
            'attr'          => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.social.Twitter.share.text.pagetitle'
            )
        ));

        $builder->add('via', 'text', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.social.Twitter.share.via',
            'required'      => false,
            'attr'          => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.social.Twitter.share.username',
                'preaddon'    => 'symbol-at'
            )
        ));

        $builder->add('related', 'text', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.social.Twitter.share.related',
            'required'      => false,
            'attr'          => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.social.Twitter.share.username',
                'preaddon'    => 'symbol-at'
            )
        ));

        $builder->add('hashtags', 'text', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.social.Twitter.share.hashtag',
            'required'      => false,
            'attr'          => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.social.Twitter.share.hashtag.placeholder',
                'preaddon'    => 'symbol-hashtag'
            )
        ));

        $builder->add('size', 'checkbox', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.social.Twitter.share.largesize',
            'value'         => 'large',
            'required'      => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_twitter";
    }
}
