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
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class IntegrationsListType
 *
 * @package Mautic\AddonBundle\Form\Type
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
        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, $options['supported_features'], true);
        $integrations       = array();

        foreach ($integrationObjects as $name => $object) {
            $settings  = $object->getIntegrationSettings();
            $addon     = $settings->getAddon();

            if ($addon->isEnabled() && $settings->isPublished()) {
                if (!isset($integrations[$settings->getAddon()->getName()])) {
                    $integrations[$settings->getAddon()->getName()] = array();
                }
                $integrations[$settings->getAddon()->getName()][$object->getName()] = $object->getDisplayName();
            }
        }

        $builder->add('integration', 'choice', array(
            'choices'     => $integrations,
            'expanded'    => false,
            'label_attr'  => array('class' => 'control-label'),
            'multiple'    => false,
            'label'       => 'mautic.integration.integrations',
            'attr'        => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.integration.integrations.tooltip',
            ),
            'required'    => false
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $integrationObjects) {
            $form = $event->getForm();
            $data = $event->getData();

            if (isset($data['integration'])) {
                if (isset($integrationObjects[$data['integration']])) {
                    $integrationObjects[$data['integration']]->appendToForm($form['properties'], 'integration');
                }
            }
        });

        $builder->add('properties', 'collection', array(
            'label' => false
        ));
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
