<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FormExitSubscriber
 *
 * @package Mautic\CoreBundle\Form\EventListener
 */
class FormExitSubscriber implements EventSubscriberInterface
{

    private $msg;

    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA  => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        //add a hidden field that is used exclusively to warn a user to use save/cancel to exit a form
        $form = $event->getForm();

        $form->add('inForm', 'hidden', array(
            'data'     => $this->msg,
            'required' => false,
            'mapped'   => false,
            'attr'     => array('class' => 'prevent-nonsubmit-form-exit')
        ));
    }
}