<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\EventListener;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Clean data before persisting to DB.
 */
final readonly class CleanFormSubscriber
{
    /**
     * @param string|mixed[] $masks
     */
    public function __construct(
        private string|array $masks = 'clean',
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: FormEvents::PRE_SUBMIT)]
    public function preSubmitData(FormEvent $event): void
    {
        $data = $event->getData();

        // clean the data
        $data = InputHelper::_($data, $this->masks);

        $event->setData($data);
    }
}
