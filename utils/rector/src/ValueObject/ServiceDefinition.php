<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

final readonly class ServiceDefinition
{
    /**
     * @param ServiceTag[] $serviceTags
     */
    public function __construct(
        private string $className,
        private array $serviceTags,
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return ServiceTag[]
     */
    public function getServiceTags(): array
    {
        return $this->serviceTags;
    }
}
