<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class SkipPublicActionMethodController extends AbstractController
{
    public function __construct()
    {
    }

    public function indexAction(): Response
    {
        return new Response('index');
    }

    public function __invoke(): Response
    {
        return new Response('invoked');
    }

    #[Required]
    public function setSomething(): void
    {
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices();
    }

    protected function getModelName(): string
    {
        return 'some.model';
    }
}
