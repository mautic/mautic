<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Extension;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomFormEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;

class CustomFormExtension extends AbstractTypeExtension
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * FormTypeCaptchaExtension constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Fetch plugin subscribers/listeners
        if ($this->dispatcher->hasListeners(CoreEvents::ON_FORM_TYPE_BUILD)) {
            $event = $this->dispatcher->dispatch(
                CoreEvents::ON_FORM_TYPE_BUILD,
                new CustomFormEvent($builder->getName(), $builder->getType()->getName())
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
     * @return string
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
