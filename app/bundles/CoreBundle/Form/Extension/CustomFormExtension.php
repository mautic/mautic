<?php

namespace Mautic\CoreBundle\Form\Extension;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomFormEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;

trigger_deprecation('mautic/core', '4.3', 'The "%s" class is deprecated, will be removed in 5.0.', CustomFormExtension::class);

/**
 * @deprecated since M4, will be removed in M5 because it's not used
 */
class CustomFormExtension extends AbstractTypeExtension
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Fetch plugin subscribers/listeners
        if ($this->dispatcher->hasListeners(CoreEvents::ON_FORM_TYPE_BUILD)) {
            $event = $this->dispatcher->dispatch(
                CoreEvents::ON_FORM_TYPE_BUILD,
                new CustomFormEvent($builder->getName(), $builder->getType()->getBlockPrefix(), $builder)
            );

            if ($listeners = $event->getListeners()) {
                foreach ($listeners as $formEvent => $listeners) {
                    foreach ($listeners as $listener) {
                        $builder->addEventListener(
                            $formEvent,
                            function (FormEvent $event) use ($options, $formEvent, $listener) {
                                call_user_func($listener, $event, $options, $formEvent);
                            },
                            -10
                        );
                    }
                }
            }

            if ($subscribers = $event->getSubscribers()) {
                foreach ($subscribers as $subcriber) {
                    $builder->addEventSubscriber($subcriber);
                }
            }
        }
    }

    /**
     * @return iterable<string>
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
