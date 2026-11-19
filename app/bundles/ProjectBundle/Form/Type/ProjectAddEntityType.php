<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class ProjectAddEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'entityType',
            HiddenType::class,
            [
                'data' => $options['entityType'],
            ]
        );

        $builder->add(
            'projectId',
            HiddenType::class,
            [
                'data' => $options['projectId'],
            ]
        );

        $builder->add(
            'entityIds',
            ProjectListEntityType::class,
            [
                'label'            => 'mautic.project.form.select_entities',
                'required'         => true,
                'multiple'         => true,
                'entityType'       => $options['entityType'],
                'projectId'        => $options['projectId'],
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text' => false,
                'save_text'  => 'mautic.core.form.add',
                'save_icon'  => 'ri-add-line',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entityType' => 'email',
            'projectId'  => null,
        ]);

        $resolver->setRequired(['entityType', 'projectId']);
        $resolver->setAllowedTypes('entityType', 'string');
        $resolver->setAllowedTypes('projectId', ['int', 'string']);
    }
}
