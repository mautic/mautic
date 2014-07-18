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
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaDetailsType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class SocialMediaDetailsType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('isPublished', 'choice', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'label'         => 'mautic.lead.social.form.enabled',
            'empty_value'   => false,
            'required'      => false
        ));

        $keys = $options['sm_object']->getRequiredKeyFields();
        $builder->add('apiKeys', 'socialmedia_keys', array(
            'label'    => false,
            'required' => false,
            'sm_keys'  => $keys
        ));

        $fields = $options['sm_object']->getIntegrationFields($options['sm_service']);
        if (!empty($fields)) {
            $builder->add('leadFields', 'socialmedia_fields', array(
                'label'       => 'mautic.lead.social.fieldassignments',
                'required'    => false,
                'lead_fields' => $options['lead_fields'],
                'sm_fields'   => $fields
            ));
        }

        $builder->add('service', 'hidden', array('data' => $options['sm_service']));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\LeadBundle\Entity\SocialMedia'
        ));

        $resolver->setRequired(array('sm_service', 'sm_object', 'lead_fields'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_details";
    }
}