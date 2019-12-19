<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Middleware;

use AppKernel;
use ReflectionClass;
use ReflectionException;
use SplPriorityQueue;
use Stack\StackedHttpKernel;

class MiddlewareBuilder
{
    /**
     * @var SplPriorityQueue|ReflectionClass[]
     */
    protected $specs;

    public function __construct()
    {
        $this->specs = new SplPriorityQueue();
    }

    /**
     * @param AppKernel $app
     *
     * @return StackedHttpKernel
     *
     * @throws ReflectionException
     */
    public function resolve(AppKernel $app): StackedHttpKernel
    {
        $this->loadMiddlewaresFromDirectory(__DIR__);

        $env    = $app->getEnvironment();
        $envDir = __DIR__.'/'.ucfirst($env);

        if (file_exists($envDir)) {
            $this->loadMiddlewaresFromDirectory($envDir, $env);
        }

        $middlewares = [$app];

        foreach ($this->specs as $spec) {
            $app = $spec->newInstanceArgs([$app]);

            array_unshift($middlewares, $app);
        }

        return new StackedHttpKernel($app, $middlewares);
    }

    /**
     * @param string      $directory
     * @param string|null $env
     *
     * @throws ReflectionException
     */
    private function loadMiddlewaresFromDirectory(string $directory, ?string $env = null): void
    {
        $middlewares = glob($directory.'/*Middleware.php');

        if (!empty($middlewares)) {
            $this->addMiddlewares($middlewares, $env);
        }
    }

    /**
     * @param array       $middlewares
     * @param string|null $env
     *
     * @throws ReflectionException
     */
    private function addMiddlewares(array $middlewares, ?string $env = null)
    {
        $prefix = 'Mautic\\Middleware\\';

        if ($env) {
            $prefix .= ucfirst($env).'\\';
        }

        foreach ($middlewares as $middleware) {
            $this->push($prefix.basename(substr($middleware, 0, -4)));
        }
    }

    /**
     * @param string $kernelClass
     *
     * @throws ReflectionException
     */
    private function push(string $kernelClass): void
    {
        $reflection = new ReflectionClass($kernelClass);
        $priority   = $reflection->getConstant('PRIORITY');

        $this->specs->insert($reflection, $priority);
    }
}
