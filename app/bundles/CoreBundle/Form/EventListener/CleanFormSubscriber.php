<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @var string|mixed[]
     */
    private $masks;

    /**
     * @param string|mixed[] $masks
     */
    public function __construct($masks = 'clean')
    {
        $this->masks = $masks;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmitData',
        ];
    }

    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        // clean the data
        $data = InputHelper::_($data, $this->masks);

        $event->setData($data);
    }
}
