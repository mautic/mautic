<?php

namespace MauticPlugin\MauticCrmBundle\Form\Type;

use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
class IntegrationCampaignsTaskType extends AbstractType
{
    public function __construct(
        private ConnectwiseIntegration $connectwiseIntegration,
    ) {
    }

    /**
     * @param FormBuilderInterface<array<mixed>|null> $builder
     * @param array<string, mixed>                    $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'activity_name',
            TextType::class,
            [
                'label'       => 'mautic.connectwise.activity.name',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new Callback(
                        function ($validateMe, ExecutionContextInterface $context): void {
                            $data = $context->getRoot()->getData();
                            if (!empty($data['properties']['config']['push_activities']) && empty($validateMe)) {
                                $context->buildViolation('mautic.core.value.required')->addViolation();
                            }
                        }
                    ),
                ],
            ]
        );

        $builder->add(
            'campaign_activity_type',
            ChoiceType::class,
            [
                'choices'           => array_flip($this->connectwiseIntegration->getActivityTypes()), // Choice type expects labels as keys
                'attr'              => ['class' => 'form-control'],
                'label'             => 'mautic.plugin.integration.campaigns.connectwise.activity.type',
                'required'          => false,
            ]
        );

        $builder->add(
            'campaign_members',
            ChoiceType::class,
            [
                'choices'           => array_flip($this->connectwiseIntegration->getMembers()),  // Choice type expects labels as keys
                'attr'              => ['class' => 'form-control'],
                'label'             => 'mautic.plugin.integration.campaigns.connectwise.members',
                'constraints'       => [
                    new Callback(
                        function ($validateMe, ExecutionContextInterface $context): void {
                            $data = $context->getRoot()->getData();
                            if (!empty($data['properties']['config']['push_activities']) && empty($validateMe)) {
                                $context->buildViolation('mautic.core.value.required')->addViolation();
                            }
                        }
                    ),
                ],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'integration_campaign_task';
    }
}
