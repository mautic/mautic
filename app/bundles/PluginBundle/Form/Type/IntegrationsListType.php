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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class IntegrationsListType.
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, $options['supported_features'], true);
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
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.integration.integration.tooltip',
                    'onchange' => 'Mautic.getIntegrationConfig(this);',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $formModifier = function (FormInterface $form, $data) use ($integrationObjects, $integrationHelper) {
            $statusChoices   = [];
            $campaignChoices = [];

            if (isset($data['integration'])) {
                $integration       = $data['integration'];
                $integrationObject = $integrationHelper->getIntegrationObject($data['integration']);
                if (method_exists($integrationObject, 'getCampaigns')) {
                    $campaigns = $integrationObject->getCampaigns();

                    if (isset($campaigns['records']) && !empty($campaigns['records'])) {
                        foreach ($campaigns['records'] as $campaign) {
                            $campaignChoices[$campaign['Id']] = $campaign['Name'];
                        }
                    }
                }
                if (method_exists($integrationObject, 'getCampaignMemberStatus') && isset($data['config']['campaigns'])) {
                    $campaignStatus = $integrationObject->getCampaignMemberStatus($data['config']['campaigns']);

                    if (isset($campaignStatus['records']) && !empty($campaignStatus['records'])) {
                        foreach ($campaignStatus['records'] as $campaignS) {
                            $statusChoices[$campaignS['Label']] = $campaignS['Label'];
                        }
                    }
                }
            }
            $form->add(
                'config',
                'integration_config',
                [
                    'label' => false,
                    'attr'  => [
                        'class' => 'integration-config-container',
                    ],
                    'integration' => (isset($integrationObjects[$data['integration']])) ? $integrationObjects[$data['integration']] : null,
                    'campaigns'   => $campaignChoices,
                    'data'        => (isset($data['config'])) ? $data['config'] : [],
                ]
            );
            $hideClass = (isset($data['campaign_member_status'])) ? '' : ' hide';
            $form->add(
                'campaign_member_status',
                'integration_campaign_status',
                [
                    'label' => false,
                    'attr'  => [
                        'class' => 'integration-campaigns-status'.$hideClass,
                    ],
                    'campaignContactStatus' => $statusChoices,
                    'data'                  => (isset($data['campaign_member_status'])) ? $data['campaign_member_status'] : [],
                ]
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );

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
        return 'integration_list';
    }
}
