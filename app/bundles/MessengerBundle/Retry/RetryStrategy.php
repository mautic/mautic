<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Retry;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

class RetryStrategy implements RetryStrategyInterface
{
    private RetryStrategyInterface $retryStrategy;

    public function __construct(
        private CoreParametersHelper $parametersHelper,
    ) {
    }

    public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
    {
        return $this->getRetryStrategy()->isRetryable($message);
    }

    public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
    {
        return $this->getRetryStrategy()->getWaitingTime($message);
    }

    private function getRetryStrategy(): RetryStrategyInterface
    {
        if (!isset($this->retryStrategy)) {
            $this->retryStrategy = new MultiplierRetryStrategy(
                (int) $this->parametersHelper->get('messenger_retry_strategy_max_retries'),
                (int) $this->parametersHelper->get('messenger_retry_strategy_delay'),
                (float) $this->parametersHelper->get('messenger_retry_strategy_multiplier'),
                (int) $this->parametersHelper->get('messenger_retry_strategy_max_delay'),
            );
        }

        return $this->retryStrategy;
    }
}
