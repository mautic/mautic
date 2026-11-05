<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ServiceUsedInternallyEvent extends Event
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function label(string $key): string
    {
        return $this->translator->trans($key);
    }
}
