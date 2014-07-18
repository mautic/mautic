<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaServiceType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class SocialMediaFieldsType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        foreach ($options['sm_fields'] as $field => $label) {
            $builder->add($field, 'choice', array(
                'choices'    => $options['lead_fields'],
                'label'      => $label,
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class'   => 'form-control')
            ));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('sm_fields', 'lead_fields'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_fields";
    }
}