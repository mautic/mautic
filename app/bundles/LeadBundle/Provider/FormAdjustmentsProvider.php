<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Event\FormAdjustmentEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

final class FormAdjustmentsProvider implements FormAdjustmentsProviderInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param FormInterface<FormInterface<mixed>> $form
     * @param mixed[]                             $fieldDetails
     *
     * @return FormInterface<FormInterface<mixed>>
     */
    public function adjustForm(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails): FormInterface
    {
        $event = new FormAdjustmentEvent($form, $fieldAlias, $fieldObject, $operator, $fieldDetails);
        $this->dispatcher->dispatch($event, LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD);

        return $event->getForm();
    }
}
