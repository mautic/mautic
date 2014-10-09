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

    private $model;
    private $options;

    public function __construct($model, $options = array())
    {
        $this->model   = $model;
        $this->options = $options;
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA  => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $id = !empty($this->options['data']) ? $this->options['data']->getId() : 0;
        if ($id && empty($this->options['ignore_formexit'])) {
            //add a hidden field that is used exclusively to warn a user to use save/cancel to exit a form
            $form = $event->getForm();

            $form->add('unlockModel', 'hidden', array(
                'data'     => $this->model,
                'required' => false,
                'mapped'   => false,
                'attr'     => array('class' => 'form-exit-unlock-model')
            ));

            $form->add('unlockId', 'hidden', array(
                'data'     => $id,
                'required' => false,
                'mapped'   => false,
                'attr'     => array('class' => 'form-exit-unlock-id')
            ));

            if (isset($this->options['unlockParameter'])) {
                $form->add('unlockParameter', 'hidden', array(
                    'data'     => $this->options['unlockParameter'],
                    'required' => false,
                    'mapped'   => false,
                    'attr'     => array('class' => 'form-exit-unlock-parameter')
                ));
            }
        }
    }
}