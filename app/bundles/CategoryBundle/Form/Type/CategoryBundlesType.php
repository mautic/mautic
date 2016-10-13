<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Form\Type;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CategoryBundlesType.
 */
class CategoryBundlesType extends AbstractType
{
    private $translator;

    private $canViewOther;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->dispatcher = $factory->getDispatcher();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $dispatcher = $this->dispatcher;

        $resolver->setDefaults([
            'choices' => function (Options $options) use ($dispatcher) {
                if ($dispatcher->hasListeners(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD)) {
                    $event = new CategoryTypesEvent();
                    $dispatcher->dispatch(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD, $event);
                    $types = $event->getCategoryTypes();
                } else {
                    $types = [];
                }

                return $types;
            },
            'expanded' => false,
            'multiple' => false,
            'required' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'category_bundles_form';
    }

    public function getParent()
    {
        return 'choice';
    }
}
