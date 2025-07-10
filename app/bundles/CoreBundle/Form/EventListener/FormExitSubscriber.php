<?php

namespace Mautic\CoreBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FormExitSubscriber implements EventSubscriberInterface
{
    /**
     * @param string $model
     * @param array  $options
     */
    public function __construct(
        private $model,
        private $options = [],
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        $id = !empty($this->options['data']) ? $this->options['data']->getId() : 0;
        if ($id && empty($this->options['ignore_formexit'])) {
            // add a hidden field that is used exclusively to warn a user to use save/cancel to exit a form
            $form = $event->getForm();

            $form->add(
                'unlockModel',
                HiddenType::class,
                [
                    'data'     => $this->model,
                    'required' => false,
                    'mapped'   => false,
                    'attr'     => ['class' => 'form-exit-unlock-model'],
                ]
            );

            $form->add(
                'unlockId',
                HiddenType::class,
                [
                    'data'     => $id,
                    'required' => false,
                    'mapped'   => false,
                    'attr'     => ['class' => 'form-exit-unlock-id'],
                ]
            );

            if (isset($this->options['unlockParameter'])) {
                $form->add(
                    'unlockParameter',
                    HiddenType::class,
                    [
                        'data'     => $this->options['unlockParameter'],
                        'required' => false,
                        'mapped'   => false,
                        'attr'     => ['class' => 'form-exit-unlock-parameter'],
                    ]
                );
            }
        }
    }
}
