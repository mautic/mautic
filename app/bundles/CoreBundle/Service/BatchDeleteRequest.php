<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class BatchDeleteRequest
{
    /**
     * @var array<string, mixed>
     */
    private array $getEntitiesArgs = [];

    private ?string $filterAlias = null;

    private ?string $permissionBase = null;

    private \Closure $isLocked;

    /**
     * @param array<string, mixed> $postActionVars
     */
    public function __construct(
        private readonly array $postActionVars,
        private readonly string $ids,
        private readonly string $searchValue,
        private readonly string $modelName,
        callable $isLocked,
    ) {
        $this->isLocked = \Closure::fromCallable($isLocked);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPostActionVars(): array
    {
        return $this->postActionVars;
    }

    public function getIds(): string
    {
        return $this->ids;
    }

    public function getSearchValue(): string
    {
        return $this->searchValue;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEntitiesArgs(): array
    {
        return $this->getEntitiesArgs;
    }

    /**
     * @param array<string, mixed> $getEntitiesArgs
     */
    public function withGetEntitiesArgs(array $getEntitiesArgs): self
    {
        $this->getEntitiesArgs = $getEntitiesArgs;

        return $this;
    }

    public function getFilterAlias(): ?string
    {
        return $this->filterAlias;
    }

    public function withFilterAlias(?string $filterAlias): self
    {
        $this->filterAlias = $filterAlias;

        return $this;
    }

    public function getPermissionBase(): ?string
    {
        return $this->permissionBase;
    }

    public function withPermissionBase(?string $permissionBase): self
    {
        $this->permissionBase = $permissionBase;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLockedFlash(object $entity): array
    {
        $lockedFlash = ($this->isLocked)($this->postActionVars, $entity, $this->modelName, true);
        \assert(is_array($lockedFlash));

        return $lockedFlash;
    }
}
