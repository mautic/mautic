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

final class SomeHelper
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

    private SomeHelper $helper;

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

    public function getManager(): SomeManager
    {
        return $this->manager;
    }

    public function getHelper(): SomeHelper
    {
        return $this->helper;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntity(): SomeEntity
    {
        return $this->entity;
    }

    private function getPrivateRepository(): SomeRepository
    {
        return $this->repository;
    }

    public static function getStaticRepository(SomeRepository $repository): SomeRepository
    {
        return $repository;
    }

    public function getComputedRepository(): SomeRepository
    {
        $repository = $this->repository;

        return $repository;
    }
}

interface RepositoryAwareInterface
{
    public function getRepository(): SomeRepository;
}

final class ContractServiceGetter implements RepositoryAwareInterface
{
    private SomeRepository $repository;

    public function getRepository(): SomeRepository
    {
        return $this->repository;
    }
}
