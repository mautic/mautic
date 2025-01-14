<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Tightenco\Collect\Support\Collection;

class ConfigMetadata
{
    /**
     * @var BundleMetadata
     */
    private $metadata;

    /**
     * @var array
     */
    private $ipLookupServices = [];

    public function __construct(BundleMetadata $metadata)
    {
        $this->metadata = $metadata;
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

        $config = (new \Tightenco\Collect\Support\Collection($config));
        $config->transform(
            function ($configGroup, string $configGroupName) {
                if (!is_array($configGroup)) {
                    return $configGroup;
                }

                $configGroup = new \Tightenco\Collect\Support\Collection($configGroup);

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

                $serviceGroup = new \Tightenco\Collect\Support\Collection($serviceGroup);
                $filtered     = $serviceGroup->reject(
                    function ($serviceDefinition) {
                        // Remove optional services (has argument optional = true) if the service class does not exist
                        return is_array($serviceDefinition)
                            && isset($serviceDefinition['optional'])
                            && true === $serviceDefinition['optional']
                            && isset($serviceDefinition['class'])
                            && false === class_exists($serviceDefinition['class']);
                    }
                );

                $filtered->transform(
                    function ($serviceDefinition) {
                        return $this->encodeParameters($serviceDefinition);
                    }
                );

                return $filtered->toArray();
            }
        );

        return $serviceGroups->toArray();
    }

    private function prepareParameters(Collection $parameters): array
    {
        $parameters->transform(
            function ($parameterValue) {
                return $this->encodeParameters($parameterValue);
            }
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
