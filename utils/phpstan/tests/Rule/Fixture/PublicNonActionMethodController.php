<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\HttpFoundation\Response;

final class PublicNonActionMethodController
{
    public function getModelName(): string
    {
        return 'some.model';
    }

    public function indexAction(): Response
    {
        return new Response('index');
    }
}
