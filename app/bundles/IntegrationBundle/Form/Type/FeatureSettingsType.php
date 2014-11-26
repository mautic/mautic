<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FeatureSettingsType
 *
 * @package Mautic\IntegrationBundle\Form\Type
 */
class FeatureSettingsType extends AbstractType
{

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $connector = $options['connector'];

        $connector_object = $options['connector_object'];

        $class  = explode('\\', get_class($connector_object));
        $exists = class_exists('\\MauticAddon\\' . $class[1] . '\\Form\\Type\\' . $connector . 'Type');

        if ($exists) {
            $builder->add('shareButton', 'socialmedia_' . strtolower($connector), array(
                'label'    => false,
                'required' => false
            ));
        }

        $connectorHelper = $this->factory->getHelper('connector');
        $fields        = $connectorHelper->getAvailableFields($options['connector']);

        if (!empty($fields)) {
            $builder->add('shareBtnMsg', 'spacer', array(
                'text' => 'mautic.connector.form.profile'
            ));

            $builder->add('leadFields', 'connector_fields', array(
                'label'            => 'mautic.connector.leadfield_matches',
                'required'         => false,
                'lead_fields'      => $options['lead_fields'],
                'data'             => isset($options['data']['leadFields']) ? $options['data']['leadFields'] : array(),
                'connector_fields' => $fields
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('connector', 'connector_object', 'lead_fields'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return "connector_featuresettings";
    }
}