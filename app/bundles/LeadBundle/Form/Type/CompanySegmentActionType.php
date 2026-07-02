<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CompanySegmentActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'addToLists',
            CompanySegmentListType::class,
            [
                'label'      => 'mautic.company_segments.campaign.events.addtolists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]
        );

        $builder->add(
            'removeFromLists',
            CompanySegmentListType::class,
            [
                'label'      => 'mautic.company_segments.campaign.events.removefromlists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'companysegment_action';
    }
}
