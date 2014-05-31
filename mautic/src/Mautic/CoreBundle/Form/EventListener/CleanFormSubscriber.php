<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\EventListener;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CleanFormSubscriber
 * Clean data before persisting to DB
 *
 * @package Mautic\CoreBundle\Form\EventListener
 */
class CleanFormSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SUBMIT  => 'preSubmitData');
    }

    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        //clean the data
        $data = InputHelper::clean($data);

        $event->setData($data);
    }
}
