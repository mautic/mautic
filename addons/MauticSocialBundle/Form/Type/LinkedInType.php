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
 * Class LinkedInType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class LinkedInType extends AbstractType
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

        $builder->add('counter', 'choice', array(
            'choices'     => array(
                'right' => 'mautic.connector.LinkedIn.share.counter.right',
                'top'   => 'mautic.connector.LinkedIn.share.counter.top',
                ''      => 'mautic.connector.LinkedIn.share.counter.none'
            ),
            'label'       => 'mautic.connector.LinkedIn.share.counter',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class'   => 'form-control')
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_linkedin";
    }
}