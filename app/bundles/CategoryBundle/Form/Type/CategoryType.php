<?php

namespace Mautic\CategoryBundle\Form\Type;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Category>
 */
class CategoryType extends AbstractType
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber([]));
        $builder->addEventSubscriber(new FormExitSubscriber('category.category', $options));

        if (!$options['data']->getId()) {
            // Do not allow custom bundle
            if (true == $options['show_bundle_select']) {
                // Create new category from category bundle - let user select the bundle
                $selected = $this->requestStack->getSession()->get('mautic.category.type', 'category');
                $builder->add(
                    'bundle',
                    CategoryBundlesType::class,
                    [
                        'label'      => 'mautic.core.type',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => ['class' => 'form-control'],
                        'required'   => true,
                        'data'       => $selected,
                    ]
                );
            } else {
                // Create new category directly from another bundle - preset bundle
                $builder->add(
                    'bundle',
                    HiddenType::class,
                    [
                        'data' => $options['bundle'],
                    ]
                );
            }
        }

        $builder->add(
            'title',
            TextType::class,
            [
                'label'      => 'mautic.core.title',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.category.form.alias.help',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'color',
            TextType::class,
            [
                'label'      => 'mautic.core.color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                ],
                'required' => false,
            ]
        );

        $builder->add('isPublished', YesNoButtonGroupType::class, [
            'label' => 'mautic.core.form.available',
        ]);

        $builder->add(
            'inForm',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => Category::class,
                'show_bundle_select' => false,
                'bundle'             => function (Options $options) {
                    if (!$bundle = $options['data']->getBundle()) {
                        $bundle = 'category';
                    }

                    return $bundle;
                },
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'category_form';
    }
}
