<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class IntegrationCampaignListType.
 */
class IntegrationCampaignListType extends AbstractType
{
    /**
     * @var \Mautic\PluginBundle\Helper\IntegrationHelper
     */
    private $integrationHelper;

    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationObjects = $this->integrationHelper->getIntegrationObjects(null, $options['supported_features'], true);
        $integrations       = ['' => ''];

        foreach ($integrationObjects as $name => $object) {
            $settings = $object->getIntegrationSettings();

            if ($settings->isPublished()) {
                if (!isset($integrations[$settings->getPlugin()->getName()])) {
                    $integrations[$settings->getPlugin()->getName()] = [];
                }
                $integrations[$settings->getPlugin()->getName()][$object->getName()] = $object->getDisplayName();
            }
        }

        $builder->add(
            'integration',
            'choice',
            [
                'choices'    => $integrations,
                'expanded'   => false,
                'label_attr' => ['class' => 'control-label'],
                'multiple'   => false,
                'label'      => 'mautic.integration.integration',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.integration.integration.tooltip',
                    //'onchange' => 'Mautic.getIntegrationCampaigns(this);',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $formModifier = function (FormInterface $form, $data) use ($integrationObjects) {
            $campaigns = [];
            if (isset($data['integration'])) {
                $integrationObject = $this->integrationHelper->getIntegrationObject($data['integration']);
                $campaigns         = $integrationObject->getCampaigns();
            }

            $form->add(
                'integration_campaigns',
                'choice',
                [
                    'choices' => $campaigns,
                    'label'   => 'mautic.integration.campaigns',
                    'attr'    => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.integration.integration.tooltip',
                    ],
                ]
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['supported_features']);
        $resolver->setDefaults(
            [
                'supported_features' => 'push_lead',
            ]
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_campaign_list';
    }
}
