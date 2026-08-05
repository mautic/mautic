<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\FieldGroupRepository;
use Mautic\LeadBundle\Form\DataTransformer\FieldGroupToOrderTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<FieldGroup>
 */
final class FieldGroupType extends AbstractType
{
    public function __construct(
        private readonly FieldGroupRepository $fieldGroupRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.field_group', $options));

        $currentGroup = $options['data'] ?? null;
        $excludeId    = $currentGroup instanceof FieldGroup ? $currentGroup->getId() : null;

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        // The "Group order" dropdown is a GUI convenience; the API sets the
        // `order` property directly, so it opts out via include_order_field.
        if ($options['include_order_field']) {
            $builder->add(
                'order',
                EntityType::class,
                [
                    'label'                     => 'mautic.lead.field_group.order',
                    'label_attr'                => ['class' => 'control-label'],
                    'class'                     => FieldGroup::class,
                    'choice_label'              => 'name',
                    'choice_translation_domain' => false,
                    'required'                  => false,
                    'placeholder'               => 'mautic.core.form.chooseone',
                    'attr'                      => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.lead.field_group.order.help',
                    ],
                    'query_builder' => function (EntityRepository $er) use ($excludeId) {
                        $qb = $er->createQueryBuilder('fg')->orderBy('fg.order', 'ASC');
                        if (null !== $excludeId) {
                            $qb->where('fg.id != :excludeId')->setParameter('excludeId', $excludeId);
                        }

                        return $qb;
                    },
                ]
            );
            $builder->get('order')->addModelTransformer(new FieldGroupToOrderTransformer($this->fieldGroupRepository));
        }

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'          => FieldGroup::class,
            'include_order_field' => true,
        ]);
        $resolver->setAllowedTypes('include_order_field', 'bool');
    }
}
