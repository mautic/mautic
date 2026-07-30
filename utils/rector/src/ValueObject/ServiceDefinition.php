<?php

declare(strict_types=1);

namespace Utils\Rector\ValueObject;

final readonly class ServiceDefinition
{
    /**
     * @param ServiceArgument[]   $serviceArguments
     * @param ServiceTag[]        $serviceTags
     * @param ServiceMethodCall[] $serviceMethodCalls
     * @param string[]            $serviceAliases
     */
    public function __construct(
        private string $className,
        private array $serviceArguments,
        private array $serviceTags,
        private array $serviceMethodCalls,
        private array $serviceAliases,
        private ?ServiceFactory $serviceFactory,
        private bool $isAutowired,
    ) {
    }

    /**
     * A service whose constructor autowiring cannot fill is registered the way ServicePass registers it:
     * with no arguments and no autowiring.
     */
    public function isAutowired(): bool
    {
        return $this->isAutowired;
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

    /**
     * @return ServiceMethodCall[]
     */
    public function getServiceMethodCalls(): array
    {
        return $this->serviceMethodCalls;
    }

    /**
     * @return string[]
     */
    public function getServiceAliases(): array
    {
        return $this->serviceAliases;
    }

    /**
     * @return ServiceFactory|null null when the service is built by its own constructor
     */
    public function getServiceFactory(): ?ServiceFactory
    {
        return $this->serviceFactory;
    }
}
