<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class ListActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'addToLists',
            LeadListType::class,
            [
                'label'      => 'mautic.lead.lead.events.addtolists',
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
            LeadListType::class,
            [
                'label'      => 'mautic.lead.lead.events.removefromlists',
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
        return 'leadlist_action';
    }
}
