<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Twig\Environment;
use Twig\Extension\AbstractExtension;

final class ServiceInTwigExtensionMethod extends AbstractExtension
{
    /**
     * @param mixed[] $context
     */
    public function includeWithEvent(Environment $environment, array $context, string $template): string
    {
        return $environment->render($template, $context);
    }
}
