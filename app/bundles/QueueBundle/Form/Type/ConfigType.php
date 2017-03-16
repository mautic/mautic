<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Form\Type;


use Mautic\QueueBundle\Event\QueueConfigEvent;
use Mautic\QueueBundle\QueueEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * ConfigType constructor.
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $event = new QueueConfigEvent($options);
        $this->eventDispatcher->dispatch(QueueEvents::BUILD_CONFIG, $event);

        $protocolChoices = array_merge([ '' => 'mautic.queue.config.protocol.disabled' ], $event->getProtocolChoices());
        $builder->add(
            'queue_protocol',
            'choice',
            [
                'label'      => 'mautic.queue.config.protocol',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['queue_protocol'],
                'choices'    => $protocolChoices,
                'attr' => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.queue.config.protocol',
                ],
                'required' => false,
            ]
        );

        foreach($event->getFormFields() as $formField) {
            $builder->add($formField['child'], $formField['type'], $formField['options']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'queueconfig';
    }
}
