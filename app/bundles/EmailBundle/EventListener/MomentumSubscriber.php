<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\EmailBundle\Helper\RequestStorageHelper;
use Mautic\EmailBundle\Swiftmailer\Momentum\Callback\MomentumCallbackInterface;
use Mautic\EmailBundle\Swiftmailer\Transport\MomentumTransport;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\Queue\QueueName;
use Mautic\QueueBundle\Queue\QueueService;
use Mautic\QueueBundle\QueueEvents;
use Symfony\Component\HttpFoundation\Request;

/**
 * Listeners specific for Momentum transport.
 */
class MomentumSubscriber extends CommonSubscriber
{
    /**
     * @var MomentumCallbackInterface
     */
    protected $momentumCallback;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * @var RequestStorageHelper
     */
    private $requestStorageHelper;

    /**
     * @param MomentumCallbackInterface $momentumCallback
     * @param QueueService              $queueService
     * @param RequestStorageHelper      $requestStorageHelper
     */
    public function __construct(
        MomentumCallbackInterface $momentumCallback,
        QueueService $queueService,
        RequestStorageHelper $requestStorageHelper
    ) {
        $this->momentumCallback     = $momentumCallback;
        $this->queueService         = $queueService;
        $this->requestStorageHelper = $requestStorageHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            QueueEvents::TRANSPORT_WEBHOOK    => ['onMomentumWebhookQueueProcessing', 0],
            EmailEvents::ON_TRANSPORT_WEBHOOK => ['onMomentumWebhookRequest', 0],
        ];
    }

    /**
     * Webhook handling specific to Momentum transport.
     *
     * @param QueueConsumerEvent $event
     */
    public function onMomentumWebhookQueueProcessing(QueueConsumerEvent $event)
    {
        if ($event->checkTransport(MomentumTransport::class)) {
            $payload = $event->getPayload();
            $key     = $payload['key'];
            $request = $this->requestStorageHelper->getRequest($key);
            $this->momentumCallback->processCallbackRequest($request);
            $this->requestStorageHelper->deleteCachedRequest($key);
            $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
        }
    }

    /**
     * @param TransportWebhookEvent $event
     */
    public function onMomentumWebhookRequest(TransportWebhookEvent $event)
    {
        $transport = MomentumTransport::class;
        if ($this->queueService->isQueueEnabled() && $event->transportIsInstanceOf($transport)) {
            // Beanstalk jobs are limited to 65,535 kB. Momentum can send up to 10.000 items per request.
            // One item has about 1,6 kB. Lets store the request to the cache storage instead of the job itself.
            $key       = $this->requestStorageHelper->storeRequest($transport, $event->getRequest());
            $this->queueService->publishToQueue(QueueName::TRANSPORT_WEBHOOK, ['transport' => $transport, 'key' => $key]);
            $event->stopPropagation();
        }

        // If the queue processing is disabled do nothing and let the default listener to process immediately
    }
}
