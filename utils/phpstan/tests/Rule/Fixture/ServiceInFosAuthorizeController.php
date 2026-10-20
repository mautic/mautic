<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use FOS\OAuthServerBundle\Controller\AuthorizeController;
use Twig\Environment;

final class ServiceInFosAuthorizeController extends AuthorizeController
{
    private function renderAuthorize(Environment $twig, string $template): string
    {
        return $twig->render($template);
    }
}
