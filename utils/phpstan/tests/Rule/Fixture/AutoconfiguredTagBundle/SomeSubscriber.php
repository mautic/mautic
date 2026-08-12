<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SomeSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
