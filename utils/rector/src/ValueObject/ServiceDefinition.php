<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

final readonly class ServiceDefinition
{
    /**
     * @param ServiceArgument[] $serviceArguments
     * @param ServiceTag[]      $serviceTags
     */
    public function __construct(
        private string $className,
        private array $serviceArguments,
        private array $serviceTags,
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return ServiceArgument[]
     */
    public function getServiceArguments(): array
    {
        return $this->serviceArguments;
    }

    /**
     * @return ServiceTag[]
     */
    public function getServiceTags(): array
    {
        return $this->serviceTags;
    }
}
