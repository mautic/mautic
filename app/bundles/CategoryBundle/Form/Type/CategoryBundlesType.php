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
 * Class CategoryBundlesType.
 */
class CategoryBundlesType extends AbstractType
{
    private $dispatcher;

    /**
     * CategoryBundlesType constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                if ($this->dispatcher->hasListeners(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD)) {
                    $event = $this->dispatcher->dispatch(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD, new CategoryTypesEvent());
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

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'category_bundles_form';
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
