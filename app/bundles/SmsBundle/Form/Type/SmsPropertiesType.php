<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Form\Type;

use Mautic\SmsBundle\Event\SmsPropertiesEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class SmsPropertiesType extends AbstractType
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $event = new SmsPropertiesEvent($builder, $options['data'] ?? []);
        $this->dispatcher->dispatch($event, SmsEvents::SMS_PROPERTIES);

        foreach ($event->getFields() as $formField) {
            $builder->add($formField['child'], $formField['type'], $formField['options']);
        }
    }
}
