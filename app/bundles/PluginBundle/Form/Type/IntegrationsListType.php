<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class IntegrationsListType
 *
 * @package Mautic\PluginBundle\Form\Type
 */
class IntegrationsListType extends AbstractType
{

    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, $options['supported_features'], true);
        $integrations       = array();

        foreach ($integrationObjects as $name => $object) {
            $settings  = $object->getIntegrationSettings();

            if ($settings->isPublished()) {
                if (!isset($integrations[$settings->getPlugin()->getName()])) {
                    $integrations[$settings->getPlugin()->getName()] = array();
                }
                $integrations[$settings->getPlugin()->getName()][$object->getName()] = $object->getDisplayName();
            }
        }

        $builder->add('integration', 'choice', array(
            'choices'     => $integrations,
            'expanded'    => false,
            'label_attr'  => array('class' => 'control-label'),
            'multiple'    => false,
            'label'       => 'mautic.integration.integration',
            'attr'        => array(
                'class'    => 'form-control',
                'tooltip'  => 'mautic.integration.integration.tooltip',
                'onchange' => 'Mautic.getIntegrationConfig(this);'
            ),
            'required'    => false
        ));

        $formModifier = function(FormInterface $form, $data) use ($integrationObjects) {
            $form->add('config', 'integration_config', array(
                'label'       => false,
                'attr'        => array(
                    'class' => 'integration-config-container'
                ),
                'integration' => (isset($integrationObjects[$data['integration']])) ? $integrationObjects[$data['integration']] : null,
                'data'        => (isset($data['config'])) ? $data['config'] : array()
            ));
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data    = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('supported_features'));
        $resolver->setDefaults(array(
            'supported_features' => 'push_lead'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'integration_list';
    }
}
