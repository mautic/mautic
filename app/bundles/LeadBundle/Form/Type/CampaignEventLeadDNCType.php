<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CampaignEventLeadDNCType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'condition',
            BooleanType::class,
            [
                'label'      => 'mautic.lead.lead.events.condition_donotcontact',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'channels',
            PreferenceChannelsType::class,
            [
                'label'       => 'mautic.lead.contact.channels',
                'multiple'    => true,
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );
    }
}
