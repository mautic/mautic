<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class ServiceInControllerActionController
{
    public function newAction(object $entity, FormFactoryInterface $formFactory): FormInterface
    {
        return $formFactory->create($entity::class, $entity);
    }
}
