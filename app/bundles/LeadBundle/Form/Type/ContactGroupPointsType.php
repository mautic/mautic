<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\PointBundle\Entity\GroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactGroupPointsType extends AbstractType
{
    public function __construct(
        protected GroupRepository $groupRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pointGroups  = $this->groupRepository->getEntities();
        foreach ($pointGroups as $group) {
            $builder->add(
                'score_group_'.$group->getId(),
                IntegerType::class,
                [
                    'label'      => $group->getName(),
                    'attr'       => [
                        'class'       => 'form-control',
                        'placeholder' => 'score not set',
                     ],
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                ]
            );
        }

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => false,
                'save_text'      => 'mautic.core.form.save',
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => [
                    'data-dismiss' => 'modal',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
