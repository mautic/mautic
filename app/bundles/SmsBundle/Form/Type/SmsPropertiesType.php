<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Mautic\SmsBundle\Event\SmsPropertiesEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SmsPropertiesType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $event = new SmsPropertiesEvent($builder, $options['data']);
        $this->dispatcher->dispatch(SmsEvents::SMS_PROPERTIES, $event);

        foreach ($event->getFields() as $formField) {
            $builder->add($formField['child'], $formField['type'], $formField['options']);
        }
    }
}
