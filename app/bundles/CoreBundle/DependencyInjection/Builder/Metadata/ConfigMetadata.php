<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Illuminate\Support\Collection;
use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;

class ConfigMetadata
{
    private array $ipLookupServices = [];

    public function __construct(
        private BundleMetadata $metadata,
    ) {
    }

    public function build(): void
    {
        $directory = $this->metadata->getDirectory();

        // Check for a single config file
        if (!file_exists($directory.'/Config/config.php')) {
            return;
        }

        $config = include $directory.'/Config/config.php';

        if (!is_array($config)) {
            return;
        }

        $config = (new Collection($config));
        $config->transform(
            function ($configGroup, string $configGroupName) {
                if (!is_array($configGroup)) {
                    return $configGroup;
                }

                $configGroup = new Collection($configGroup);

                switch ($configGroupName) {
                    case 'ip_lookup_services':
                        $this->ipLookupServices = array_merge($this->ipLookupServices, $configGroup->toArray());
                        break;
                    case 'services':
                        return $this->prepareServices($configGroup);
                    case 'parameters':
                        return $this->prepareParameters($configGroup);
                }

                return $configGroup->toArray();
            }
        );

        $this->metadata->setConfig($config->toArray());
    }

    public function getIpLookupServices(): array
    {
        return $this->ipLookupServices;
    }

    private function prepareServices(Collection $serviceGroups): array
    {
        $serviceGroups->transform(
            function ($serviceGroup) {
                if (!is_array($serviceGroup)) {
                    return $serviceGroup;
                }

                $serviceGroup = new Collection($serviceGroup);
                $filtered     = $serviceGroup->reject(
                    fn ($serviceDefinition): bool =>
                        // Remove optional services (has argument optional = true) if the service class does not exist
                        is_array($serviceDefinition)
                        && isset($serviceDefinition['optional'])
                        && true === $serviceDefinition['optional']
                        && isset($serviceDefinition['class'])
                        && false === class_exists($serviceDefinition['class'])
                );

                $filtered->transform(
                    fn ($serviceDefinition) => $this->encodeParameters($serviceDefinition)
                );

                return $filtered->toArray();
            }
        );

        return $serviceGroups->toArray();
    }

    private function prepareParameters(Collection $parameters): array
    {
        $parameters->transform(
            fn ($parameterValue) => $this->encodeParameters($parameterValue)
        );

        return $parameters->toArray();
    }

    /**
     *  Encodes percent signs so they are not compiled in the container.
     */
    private function encodeParameters($valueToEncode)
    {
        if (is_array($valueToEncode)) {
            foreach ($valueToEncode as $key => $value) {
                $valueToEncode[$key] = $this->encodeParameters($value);
            }

            return $valueToEncode;
        }

        return is_string($valueToEncode) ? str_replace('%', '%%', $valueToEncode) : $valueToEncode;
    }
}
