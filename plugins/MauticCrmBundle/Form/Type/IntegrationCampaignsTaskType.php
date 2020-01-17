<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Form\Type;

use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class IntegrationCampaignsTaskType extends AbstractType
{
    private $connectwiseIntegration;

    public function __construct(ConnectwiseIntegration $connectwiseIntegration)
    {
        $this->connectwiseIntegration = $connectwiseIntegration;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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
                        function ($validateMe, ExecutionContextInterface $context) {
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
                        function ($validateMe, ExecutionContextInterface $context) {
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'integration_campaign_task';
    }
}
