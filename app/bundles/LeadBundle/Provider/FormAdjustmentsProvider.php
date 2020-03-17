<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Event\FormAdjustmentEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

final class FormAdjustmentsProvider implements FormAdjustmentsProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function adjustForm(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails): FormInterface
    {
        $event = new FormAdjustmentEvent($form, $fieldAlias, $fieldObject, $operator, $fieldDetails);
        $this->dispatcher->dispatch(LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD, $event);

        return $event->getForm();
    }
}
