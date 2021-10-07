<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\CoreBundle\ParametersStorage\ParametersStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class CoreParametersHelper.
 */
class CoreParametersHelper
{
    /**
     * @var ParameterBag
     */
    private $parameters;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $resolvedParameters;

    private ParametersStorage $parametersStorage;

    public function __construct(ContainerInterface $container, ParametersStorage $parametersStorage)
    {
        $this->container         = $container;
        $this->parametersStorage = $parametersStorage;
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
            //use the constant in case in the installer
            return MAUTIC_TABLE_PREFIX;
        }

        // First check the container so that Symfony will resolve container parameters within Mautic config values
        $containerName = sprintf('mautic.%s', $name);
        if ($this->container->hasParameter($containerName)) {
            return $this->container->getParameter($containerName);
        }

        return $this->getParameters()->get($name, $default);
    }

    /**
     * @param string $name
     */
    public function has($name): bool
    {
        return $this->getParameters()->has($this->stripMauticPrefix($name));
    }

    public function all(): array
    {
        $this->getParameters();

        return $this->resolvedParameters;
    }

    /**
     * @deprecated 3.0.0 to be removed in 4.0; use get() instead
     */
    public function getParameter($name, $default = null)
    {
        return $this->get($name, $default);
    }

    private function stripMauticPrefix(string $name): string
    {
        return str_replace('mautic.', '', $name);
    }

    private function resolveParameters(): void
    {
        $all = $this->getParameters()->all();

        foreach ($all as $key => $value) {
            $this->resolvedParameters[$key] = $this->get($key, $value);
        }
    }

    private function getParameters(): ParameterBag
    {
        if (null === $this->parameters) {
            $loader           = new ParameterLoader();
            $loader->getParameterBag()->add($this->parametersStorage->getStorage()->read());
            $loader->loadIntoEnvironment();
            $this->parameters = $loader->getParameterBag();
            $this->resolveParameters();
        }

        return $this->parameters;
    }
}
