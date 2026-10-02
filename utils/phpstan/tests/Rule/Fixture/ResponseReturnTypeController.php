<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class ResponseReturnTypeController
{
    public function indexAction(): Response
    {
        return new Response('index');
    }

    public function saveAction(): JsonResponse|RedirectResponse
    {
        return new JsonResponse();
    }

    public function __invoke(): Response
    {
        return new Response('invoked');
    }

    public function getModelName(): string
    {
        return 'some.model';
    }

    private function buildSomethingAction(): string
    {
        return 'something';
    }
}
