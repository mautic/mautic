<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Loader\ParameterLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CoreParametersHelper
{
    private \Symfony\Component\HttpFoundation\ParameterBag $parameters;

    private ?array $resolvedParameters = null;

    public function __construct(
        private ContainerInterface $container,
    ) {
        $loader = new ParameterLoader();

        $this->parameters = $loader->getParameterBag();

        $this->resolveParameters();
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $name = $this->stripMauticPrefix($name);

        if ('db_table_prefix' === $name && defined('MAUTIC_TABLE_PREFIX')) {
            // use the constant in case in the installer
            return MAUTIC_TABLE_PREFIX;
        }

        // First check the container so that Symfony will resolve container parameters within Mautic config values
        $containerName = sprintf('mautic.%s', $name);
        if ($this->container->hasParameter($containerName)) {
            return $this->container->getParameter($containerName);
        }

        return $this->parameters->get($name, $default);
    }

    /**
     * @param string $name
     */
    public function has($name): bool
    {
        return $this->parameters->has($this->stripMauticPrefix($name));
    }

    public function all(): array
    {
        return $this->resolvedParameters;
    }

    private function stripMauticPrefix(string $name): string
    {
        return str_replace('mautic.', '', $name);
    }

    private function resolveParameters(): void
    {
        $all = $this->parameters->all();

        foreach ($all as $key => $value) {
            $this->resolvedParameters[$key] = $this->get($key, $value);
        }
    }
}
