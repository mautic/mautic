<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\EventListener;

use Mautic\CoreBundle\Entity\IdentifierInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final readonly class FormExitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $model,
        private array $options = [],
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var IdentifierInterface|null $data */
        $data = $this->options['data'] ?? null;
        $id   = !empty($data) ? $data->getId() : 0;
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
