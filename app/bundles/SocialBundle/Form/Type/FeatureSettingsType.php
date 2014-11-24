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
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $network = $options['sm_network'];

        /** @var \Mautic\SocialBundle\Network\AbstractNetwork $sm_object */
        $sm_object = $options['sm_object'];

        if ($sm_object->getIsCore()) {
            $exists = class_exists('\\Mautic\\SocialBundle\\Form\\Type\\' . $network . 'Type');
        } else {
            $class = explode('\\', get_class($sm_object));
            $exists = class_exists('\\MauticAddon\\' . $class[1] . '\\Form\\Type\\' . $network . 'Type');
        }

        if ($exists) {
            $builder->add('shareButton', 'socialmedia_' . strtolower($network), array(
                'label'       => false,
                'required'    => false
            ));
        }

        $networkHelper = $this->factory->getNetworkIntegrationHelper();
        $fields = $networkHelper->getAvailableFields($options['sm_network']);

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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('sm_network', 'sm_object', 'lead_fields'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "socialmedia_featuresettings";
    }
}
