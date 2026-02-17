<?php

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class CampaignEventJumpToEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $jumpProps = $builder->getData();
        $selected  = $jumpProps['jumpToEvent'] ?? null;

        $builder->add(
            'jumpToEvent',
            ChoiceType::class,
            [
                'choices'    => [],
                'multiple'   => false,
                'label'      => 'mautic.campaign.form.jump_to_event',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'                => 'form-control',
                    'data-onload-callback' => 'updateJumpToEventOptions',
                    'data-selected'        => $selected,
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        // Allows additional values (new events) to be selected before persisting
        $builder->get('jumpToEvent')->resetViewTransformers();
    }

    public function getBlockPrefix()
    {
        return 'campaignevent_jump_to_event';
    }
}
