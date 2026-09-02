<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class SomeRepository
{
}

final class SomeModel
{
}

final class SomeManager
{
}

final class SomeEntity
{
}

final class ServiceGetter
{
    private SomeRepository $repository;

    private SomeModel $model;

    private SomeManager $manager;

    private SomeEntity $entity;

    private string $name;

    public function getRepository(): SomeRepository
    {
        return $this->repository;
    }

    public function getModel(): SomeModel
    {
        return $this->model;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntity(): SomeEntity
    {
        return $this->entity;
    }

    private function getPrivateManager(): SomeManager
    {
        return $this->manager;
    }

    public static function getStaticManager(SomeManager $manager): SomeManager
    {
        return $manager;
    }

    public function getComputedManager(): SomeManager
    {
        $manager = $this->manager;

        return $manager;
    }
}
