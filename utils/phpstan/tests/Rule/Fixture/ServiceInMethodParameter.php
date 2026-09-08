<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ServiceInMethodParameter
{
    /**
     * @param mixed[] $options
     */
    public function buildForm(object $entity, FormFactoryInterface $formFactory, ?string $action = null, array $options = []): FormInterface
    {
        return $formFactory->create($entity::class, $entity, $options);
    }

    public function translate(TranslatorInterface $translator, string $key): string
    {
        return $translator->trans($key);
    }
}
