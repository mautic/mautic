<?php

namespace Mautic\CategoryBundle\Form\Type;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CategoryBundlesType extends AbstractType
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => function (Options $options): array {
                if ($this->dispatcher->hasListeners(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD)) {
                    $event = $this->dispatcher->dispatch(new CategoryTypesEvent(), CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD);
                    $types = $event->getCategoryTypes();
                } else {
                    $types = [];
                }

                return array_flip($types);
            },
            'expanded'          => false,
            'multiple'          => false,
            'required'          => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'category_bundles_form';
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
