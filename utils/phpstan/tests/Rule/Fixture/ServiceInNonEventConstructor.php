<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ServiceInNonEventConstructor
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
