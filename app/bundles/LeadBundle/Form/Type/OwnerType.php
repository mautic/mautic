<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class OwnerType extends AbstractType
{
    public const NO_OWNER_VALUE = '__none__';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $options['items'];
        if (null !== $options['remove_label']) {
            $choices = array_merge([$options['remove_label'] => self::NO_OWNER_VALUE], $choices);
        }

        $builder->add(
            'addowner',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.batch.set',
                'multiple'          => false,
                'choices'           => $choices,
                'required'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
            ]
        );

        $builder->add('ids', HiddenType::class);

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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('items');
        $resolver->setDefault('remove_label', null);
        $resolver->setAllowedTypes('remove_label', ['null', 'string']);
    }

    public function getBlockPrefix(): string
    {
        return 'lead_batch_owner';
    }
}
