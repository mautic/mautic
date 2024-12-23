<?php

namespace Mautic\PluginBundle\Form\Type;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class IntegrationsListType extends AbstractType
{
    public function __construct(
        private IntegrationHelper $integrationHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $integrationObjects = $this->integrationHelper->getIntegrationObjects(null, $options['supported_features'], true);
        $integrations       = ['' => ''];

        foreach ($integrationObjects as $object) {
            $settings = $object->getIntegrationSettings();

            if ($settings->isPublished()) {
                $pluginName = $settings->getPlugin()->getName();
                if (!isset($integrations[$pluginName])) {
                    $integrations[$pluginName] = [];
                }
                $integrations[$pluginName][$object->getDisplayName()] = $object->getName();
            }
        }

        $builder->add(
            'integration',
            ChoiceType::class,
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

        $formModifier = function (FormEvent $event) use ($integrationObjects): void {
            $data            = $event->getData();
            $form            = $event->getForm();
            $statusChoices   = [];
            $campaignChoices = [];

            if (!empty($data['integration'])) {
                $integrationObject = $this->integrationHelper->getIntegrationObject($data['integration']);
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
                IntegrationConfigType::class,
                [
                    'label' => false,
                    'attr'  => [
                        'class' => 'integration-config-container',
                    ],
                    'integration' => isset($data['integration'], $integrationObjects[$data['integration']]) ? $integrationObjects[$data['integration']] : null,
                    'campaigns'   => $campaignChoices,
                    'data'        => $data['config'] ?? [],
                ]
            );

            $hideClass = (isset($data['campaign_member_status']) && !empty($data['campaign_member_status']['campaign_member_status'])) ? '' : ' hide';
            $form->add(
                'campaign_member_status',
                IntegrationCampaignsType::class,
                [
                    'label' => false,
                    'attr'  => [
                        'class' => 'integration-campaigns-status'.$hideClass,
                    ],
                    'campaignContactStatus' => $statusChoices,
                    'data'                  => $data['campaign_member_status'] ?? [],
                ]
            );
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $formModifier);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $formModifier);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['supported_features']);
        $resolver->setDefaults(
            [
                'supported_features' => 'push_lead',
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'integration_list';
    }
}
