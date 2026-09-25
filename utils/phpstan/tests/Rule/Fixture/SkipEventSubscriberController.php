<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SkipEventSubscriberController implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['kernel.request' => 'onRequest'];
    }

    public function onRequest(): void
    {
    }
}
