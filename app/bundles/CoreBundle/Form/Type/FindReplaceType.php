<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class FindReplaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'field',
            ChoiceType::class,
            [
                'label'       => $options['field_label'],
                'required'    => true,
                'choices'     => $options['field_choices'],
                'placeholder' => 'mautic.core.form.chooseone',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'find',
            TextType::class,
            [
                'label'      => 'mautic.core.find_replace.find',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'replace',
            TextType::class,
            [
                'label'      => 'mautic.core.find_replace.replace',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add('ids', HiddenType::class);
        $builder->add(
            'all',
            HiddenType::class,
            [
                'data' => ($options['all_items'] || $options['all_contacts']) ? '1' : '0',
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => false,
                'save_text'      => 'mautic.core.find_replace.replace_all',
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

    public function getBlockPrefix(): string
    {
        return 'find_replace';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'all_contacts'  => false,
            'all_items'     => false,
            'field_label'   => 'mautic.core.find_replace.field',
            'field_choices' => [],
        ]);
    }
}
