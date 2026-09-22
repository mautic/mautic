<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Illuminate\Support\Collection;
use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;

final class ConfigMetadata
{
    private array $ipLookupServices = [];

    public function __construct(
        private readonly BundleMetadata $metadata,
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

    private function prepareParameters(Collection $parameters): array
    {
        $parameters->transform(
            fn ($parameterValue): mixed => $this->encodeParameters($parameterValue)
        );

        return $parameters->toArray();
    }

    /**
     *  Encodes percent signs so they are not compiled in the container.
     */
    private function encodeParameters(mixed $valueToEncode): mixed
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
