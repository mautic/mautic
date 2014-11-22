<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Form\Type;

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
           'text' =>  'mautic.social.form.sharebutton'
        ));

        $builder->add('counter', 'choice', array(
            'choices'     => array(
                'right' => 'mautic.social.LinkedIn.share.counter.right',
                'top'   => 'mautic.social.LinkedIn.share.counter.top',
                ''      => 'mautic.social.LinkedIn.share.counter.none'
            ),
            'label'       => 'mautic.social.LinkedIn.share.counter',
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