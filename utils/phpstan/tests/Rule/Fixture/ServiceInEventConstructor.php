<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ServiceInEventConstructor extends Event
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Request $request,
    ) {
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
