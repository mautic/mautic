<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Notificator\WebhookFailureNotificator;

class WebhookService
{
    public function __construct(private CoreParametersHelper $coreParametersHelper,
        private WebhookFailureNotificator $webhookFailureNotificator)
    {
    }

    public function getHealthyWebhookTime(): \DateTimeImmutable
    {
        $webHookHealthCheckTime = $this->coreParametersHelper->get('webhook_health_check_time', 300);

        return (new \DateTimeImmutable())->modify(sprintf('-%d seconds', $webHookHealthCheckTime));
    }

    public function isWebhookHealthy(Webhook $webhook): bool
    {
        $healthyWebhookTime = $this->getHealthyWebhookTime();

        return null === $webhook->getMarkedUnhealthyAt() || ($webhook->getMarkedUnhealthyAt() < $healthyWebhookTime);
    }

    public function sendWebhookFailureNotification(Webhook $webhook, string $reason): bool
    {
        if ($this->shouldSendFailureNotification($webhook)) {
            $this->webhookFailureNotificator->send($webhook, $reason);

            return true;
        }

        return false;
    }

    private function shouldSendFailureNotification(Webhook $webhook): bool
    {
        return $this->isFailingMoreThanThresholdTime($webhook) && $this->shouldSendNotificationNow($webhook);
    }

    private function isFailingMoreThanThresholdTime(Webhook $webhook): bool
    {
        if (null === $webhook->getUnHealthySince()) {
            return false;
        }
        $webhookFailureNotificationTime = $this->coreParametersHelper->get('first_webhook_failure_notification_time', 3600);
        $healthyWebhookTime             = (new \DateTimeImmutable())->modify(sprintf('-%d seconds', $webhookFailureNotificationTime));

        return $webhook->getUnHealthySince() < $healthyWebhookTime;
    }

    private function shouldSendNotificationNow(Webhook $webhook): bool
    {
        if (null === $webhook->getLastNotificationSentAt()) {
            return true;
        }
        $webhookFailureNotificationInterval = $this->coreParametersHelper->get('webhook_failure_notification_interval', 86400);
        $healthyWebhookTime                 = (new \DateTimeImmutable())->modify(sprintf('-%d seconds', $webhookFailureNotificationInterval));

        return $webhook->getLastNotificationSentAt() < $healthyWebhookTime;
    }
}
