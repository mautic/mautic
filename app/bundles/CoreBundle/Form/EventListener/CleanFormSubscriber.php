<?php

namespace Mautic\CoreBundle\Form\EventListener;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Clean data before persisting to DB.
 */
class CleanFormSubscriber implements EventSubscriberInterface
{
    /**
     * @param string|mixed[] $masks
     */
    public function __construct(
        private $masks = 'clean'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmitData',
        ];
    }

    public function preSubmitData(FormEvent $event): void
    {
        $data = $event->getData();

        // clean the data
        $data = InputHelper::_($data, $this->masks);

        $event->setData($data);
    }
}
