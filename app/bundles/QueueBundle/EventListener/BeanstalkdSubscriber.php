<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\EventListener;

use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\Queue\QueueProtocol;
use Mautic\QueueBundle\Queue\QueueService;
use Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class BeanstalkdSubscriber.
 */
class BeanstalkdSubscriber extends AbstractQueueSubscriber
{
    const DELAY_DURATION = 60;

    /**
     * @var string
     */
    protected $protocol = QueueProtocol::BEANSTALKD;

    /**
     * @var string
     */
    protected $protocolUiTranslation = 'mautic.queue.config.protocol.beanstalkd';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * BeanstalkdSubscriber constructor.
     *
     * @param ContainerInterface $container
     * @param QueueService       $queueService
     */
    public function __construct(ContainerInterface $container, QueueService $queueService)
    {
        // The container is needed due to non-required binding of pheanstalk
        $this->container    = $container;
        $this->queueService = $queueService;
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function publishMessage(Events\QueueEvent $event)
    {
        $this->container->get('leezy.pheanstalk')
            ->useTube($event->getQueueName())
            ->put(serialize($event->getPayload()));
    }

    /**
     * @param Events\QueueEvent $event
     *
     * @throws Pheanstalk\Exception\ServerException
     */
    public function consumeMessage(Events\QueueEvent $event)
    {
        $messagesConsumed = 0;

        while ($event->getMessages() === null || $event->getMessages() > $messagesConsumed) {
            $pheanstalk = $this->container->get('leezy.pheanstalk');
            $job        = $pheanstalk
                ->watch($event->getQueueName())
                ->ignore('default')
                ->reserve(3600);

            $consumerEvent = $this->queueService->dispatchConsumerEventFromPayload($job->getData());

            if ($consumerEvent->getResult() === QueueConsumerResults::TEMPORARY_REJECT) {
                $pheanstalk->release($job, PheanstalkInterface::DEFAULT_PRIORITY, static::DELAY_DURATION);
            } elseif ($consumerEvent->getResult() === QueueConsumerResults::ACKNOWLEDGE) {
                try {
                    $pheanstalk->delete($job);
                } catch (Pheanstalk\Exception\ServerException $e) {
                    if (strpos($e->getMessage(), 'Cannot delete job') === false
                        && strpos($e->getMessage(), 'NOT_FOUND') === false
                    ) {
                        throw $e;
                    }
                }
            } elseif ($consumerEvent->getResult() === QueueConsumerResults::REJECT) {
                $pheanstalk->bury($job);
            }

            ++$messagesConsumed;
        }
    }

    /**
     * @param Events\QueueConfigEvent $event
     */
    public function buildConfig(Events\QueueConfigEvent $event)
    {
        $options        = $event->getOptions();
        $showConditions = '{"config_queueconfig_queue_protocol":["beanstalkd"]}';

        $event->addFormField(
            'beanstalkd_host',
            'text',
            [
                'label'      => 'mautic.queue.config.host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.host.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['beanstalkd_host']) ? 'localhost' : $options['data']['beanstalkd_host'],
            ]
        );

        $event->addFormField(
            'beanstalkd_port',
            'text',
            [
                'label'      => 'mautic.queue.config.port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.port.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['beanstalkd_port']) ? '11300' : $options['data']['beanstalkd_port'],
            ]
        );

        $event->addFormField(
            'beanstalkd_timeout',
            'text',
            [
                'label'      => 'mautic.queue.config.beanstalkd.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.beanstalkd.timeout.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['beanstalkd_timeout']) ? '60' : $options['data']['beanstalkd_timeout'],
            ]
        );
    }
}
