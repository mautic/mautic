<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class CampaignEventLeadOwnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'owner',
            UserListType::class,
            [
                'label'      => 'mautic.lead.lead.field.owner',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
                'multiple' => true,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_lead_owner';
    }
}
