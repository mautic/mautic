<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaServiceType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class FeatureSettingsType extends AbstractType
{

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $network = $options['sm_network'];
        if (class_exists('\\Mautic\\SocialBundle\\Form\\Type\\' . $network . 'Type')) {
            $builder->add('shareButton', 'socialmedia_'.strtolower($network), array(
                'label'       => false,
                'required'    => false
            ));
        }

        $fields = NetworkIntegrationHelper::getAvailableFields($this->factory, $options['sm_network']);
        if (!empty($fields)) {
            $builder->add('shareBtnMsg', 'spacer', array(
                'text' => 'mautic.social.form.profile'
            ));

            $builder->add('leadFields', 'socialmedia_fields', array(
                'label'       => 'mautic.social.fieldassignments',
                'required'    => false,
                'lead_fields' => $options['lead_fields'],
                'data'        => isset($options['data']['leadFields']) ? $options['data']['leadFields'] : array(),
                'sm_fields'   => $fields
            ));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('sm_network', 'lead_fields'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_featuresettings";
    }
}
