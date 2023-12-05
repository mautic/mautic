<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangeOwnerType extends AbstractType
{
    private \Mautic\UserBundle\Model\UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'owner',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.batch.add_to',
                'multiple'          => false,
                'choices'           => $this->userModel->getOwnerListChoices(),
                'required'          => true,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class
        );
    }
}
