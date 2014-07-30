<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaServicesType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class ServicesType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add("sm-panel-wrapper-start", 'panel_wrapper_start', array(
            'attr' => array(
                'id' => "sm-panel"
            )
        ));

        foreach ($options['integrations'] as $service => $object) {
            $builder->add("{$service}-panel-start", 'panel_start', array(
                'label'      => 'mautic.social.' . $service,
                'dataParent' => '#sm-panel',
                'bodyId'     => $service . '-panel'
            ));

            $builder->add($service, "socialmedia_details", array(
                'label'       => false,
                'sm_service'  => $service,
                'sm_object'   => $object,
                'lead_fields' => $options['lead_fields'],
                'data'        => $options['data'][$service]
            ));

            $builder->add("{$service}-panel-end", 'panel_end');
        }

        $builder->add("sm-panel-wrapper-end", 'panel_wrapper_end');

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('integrations', 'lead_fields'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_services";
    }
}