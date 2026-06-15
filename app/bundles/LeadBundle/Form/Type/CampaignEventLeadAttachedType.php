<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

final class CampaignEventLeadAttachedType extends AbstractType
{
    public function __construct(private ListModel $listModel)
    {
    }

    /**
     * @param FormBuilderInterface<FormBuilderInterface> $builder
     * @param array<mixed>                               $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'timestamp',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.lead.events.campaigns.timestamp',
                'label_attr'        => ['class' => 'control-label'],
                'multiple'          => false,
                'choices'           => ['Campaign Start Date' => 'campaign_start_date'],
                'attr'              => ['class' => 'form-control'],
                'required'          => true,
            ]
        );

        $builder->add(
            'operator',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.lead.events.campaigns.operator',
                'multiple'          => false,
                'choices'           => $this->listModel->getOperatorsForFieldType([
                    'include' => [
                        'gt',
                        'lt',
                    ],
                ]),
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                ],
            ]
        );

        $data = (!isset($options['data']['triggerInterval']) || empty($options['data']['triggerInterval'])
            || !is_numeric($options['data']['triggerInterval'])) ? 1 : (int) $options['data']['triggerInterval'];

        $builder->add(
            'triggerInterval',
            NumberType::class,
            [
                'label'     => 'mautic.lead.lead.events.campaigns.number',
                'attr'      => [
                    'class'    => 'form-control',
                ],
                'data'      => $data,
                'required'  => true,
            ]
        );

        $data = (!empty($options['data']['triggerIntervalUnit'])) ? $options['data']['triggerIntervalUnit'] : 'd';
        $builder->add(
            'triggerIntervalUnit',
            ChoiceType::class,
            [
                'label'       => 'mautic.lead.lead.events.campaigns.unit',
                'choices'     => [
                    'mautic.campaign.event.intervalunit.choice.i' => 'i',
                    'mautic.campaign.event.intervalunit.choice.h' => 'h',
                    'mautic.campaign.event.intervalunit.choice.d' => 'd',
                    'mautic.campaign.event.intervalunit.choice.m' => 'm',
                    'mautic.campaign.event.intervalunit.choice.y' => 'y',
                ],
                'multiple'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => [
                    'class' => 'form-control',
                ],
                'placeholder' => false,
                'required'    => true,
                'data'        => $data,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'campaignevent_lead_contact_added';
    }
}
