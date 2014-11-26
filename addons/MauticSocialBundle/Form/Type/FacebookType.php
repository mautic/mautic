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
 * Class FacebookType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class FacebookType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('shareBtnMsg', 'spacer', array(
           'text' =>  'mautic.connector.form.sharebutton'
        ));

        $builder->add('layout', 'choice', array(
            'choices'     => array(
                'standard'     => 'mautic.connector.Facebook.share.layout.standard',
                'button_count' => 'mautic.connector.Facebook.share.layout.buttoncount',
                'button'       => 'mautic.connector.Facebook.share.layout.button',
                'box_count'    => 'mautic.connector.Facebook.share.layout.boxcount',
                'icon'         => 'mautic.connector.Facebook.share.layout.icon'
            ),
            'label'       => 'mautic.connector.Facebook.share.layout',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class'   => 'form-control')
        ));

        $builder->add('action', 'choice', array(
            'choices'     => array(
                'like'       => 'mautic.connector.Facebook.share.action.like',
                'recommend'  => 'mautic.connector.Facebook.share.action.recommend',
                'share'      => 'mautic.connector.Facebook.share.action.share'
            ),
            'label'       => 'mautic.connector.Facebook.share.action',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class'   => 'form-control')
        ));

        $builder->add('showFaces', 'checkbox', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.connector.Facebook.share.showfaces',
            'value'         =>  1,
            'required'      => false
        ));

        $builder->add('showShare', 'checkbox', array(
            'label_attr'    => array('class' => 'control-label'),
            'label'         => 'mautic.connector.Facebook.share.showshare',
            'value'         => 1,
            'required'      => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_facebook";
    }
}