<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\Translation\TranslatorInterface;

trait ServiceInTraitMethod
{
    private function writeCounts(TranslatorInterface $translator, string $key): string
    {
        return $translator->trans($key);
    }
}
