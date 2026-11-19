<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ServiceInInjectionMethod
{
    private RouterInterface $router;

    private TranslatorInterface $translator;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    public function autowireServiceInInjectionMethod(RouterInterface $router): void
    {
        $this->router = $router;
    }

    #[Required]
    public function setTranslator(
        TranslatorInterface $translator,
    ): void {
        $this->translator = $translator;
    }
}
