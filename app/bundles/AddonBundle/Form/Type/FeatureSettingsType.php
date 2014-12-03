<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FeatureSettingsType
 *
 * @package Mautic\AddonBundle\Form\Type
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
        $integration = $options['integration'];

        $integration_object = $options['integration_object'];

        $class  = explode('\\', get_class($integration_object));
        $exists = class_exists('\\MauticAddon\\' . $class[1] . '\\Form\\Type\\' . $integration . 'Type');

        if ($exists) {
            $builder->add('shareButton', 'socialmedia_' . strtolower($integration), array(
                'label'    => false,
                'required' => false
            ));
        }

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        try {
            $fields = $integrationHelper->getAvailableFields($options['integration'], false);
            $error = '';
        } catch (\Exception $e) {
            $fields = array();
            $error = $e->getMessage();
        }

        if (!empty($fields) || $error) {
            $builder->add('leadFields', 'integration_fields', array(
                'label'            => 'mautic.integration.leadfield_matches',
                'required'         => false,
                'lead_fields'      => $options['lead_fields'],
                'data'             => isset($options['data']['leadFields']) ? $options['data']['leadFields'] : array(),
                'integration_fields' => $fields
            ));

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($error) {
                $form = $event->getForm();

                if ($error) {
                    $form['leadFields']->addError(new FormError($error));
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('integration', 'integration_object', 'lead_fields'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return "integration_featuresettings";
    }
}