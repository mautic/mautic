<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProjectEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buttons', FormButtonsType::class);

        $builder->add(
            'name',
            TextType::class,
            [
                'label'       => 'mautic.core.name',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'required'   => false,
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'      => Project::class,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'project_entity';
    }
}
