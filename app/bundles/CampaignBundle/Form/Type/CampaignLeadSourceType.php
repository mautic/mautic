<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CampaignLeadSourceType.
 */
class CampaignLeadSourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sourceType    = $options['data']['sourceType'];
        $sourceChoices = $options['source_choices'] ?? [];
        foreach ($sourceChoices as $key => $val) {
            $sourceChoices[$key] = $val.' ('.$key.')';
        }

        switch ($sourceType) {
            case 'lists':
                $builder->add(
                    'lists',
                    ChoiceType::class,
                    [
                        'choices'           => array_flip($sourceChoices),
                        'multiple'          => true,
                        'label'             => 'mautic.campaign.leadsource.lists',
                        'label_attr'        => ['class' => 'control-label'],
                        'attr'              => [
                            'class' => 'form-control',
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
                break;
            case 'forms':
                $builder->add(
                    'forms',
                    ChoiceType::class,
                    [
                        'choices'           => array_flip($sourceChoices),
                        'multiple'          => true,
                        'label'             => 'mautic.campaign.leadsource.forms',
                        'label_attr'        => ['class' => 'control-label'],
                        'attr'              => [
                            'class' => 'form-control',
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
                break;
            default:
                break;
        }

        $builder->add('sourceType', HiddenType::class);

        $builder->add('droppedX', HiddenType::class);

        $builder->add('droppedY', HiddenType::class);

        $update = !empty($options['data'][$sourceType]);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add('buttons', FormButtonsType::class, [
            'save_text'       => $btnValue,
            'save_icon'       => $btnIcon,
            'save_onclick'    => 'Mautic.submitCampaignSource(event)',
            'apply_text'      => false,
            'container_class' => 'bottom-form-buttons',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['source_choices']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'campaign_leadsource';
    }
}
