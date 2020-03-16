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
use Mautic\CoreBundle\Cache\MiddlewareCacheWarmer;
use ReflectionClass;
use ReflectionException;
use SplPriorityQueue;
use Stack\StackedHttpKernel;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class MiddlewareBuilder
{
    /**
     * @var AppKernel
     */
    private $app;

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * MiddlewareBuilder constructor.
     */
    public function __construct(AppKernel $app)
    {
        $this->app       = $app;
        $this->cacheFile = sprintf('%s/middlewares.cache.php', $app->getCacheDir());
        $this->specs     = new SplPriorityQueue();
    }

    public function resolve(): StackedHttpKernel
    {
        $this->loadMiddlewares();

        $app         = $this->app;
        $middlewares = [$app];

        foreach ($this->specs as $spec) {
            $app = $spec->newInstanceArgs([$app]);

            array_unshift($middlewares, $app);
        }

        return new StackedHttpKernel($app, $middlewares);
    }

    private function loadMiddlewares(): void
    {
        if (!$this->hasCacheFile()) {
            $this->warmUpCacheCommand();
        }

        if (!$this->hasCacheFile()) {
            throw new FileNotFoundException('No middleware cache file found. Please warm the middleware cache first.');
        }

        $this->loadCacheFile();
    }

    private function warmUpCacheCommand(): void
    {
        $middlewareCacheWarmer = new MiddlewareCacheWarmer($this->app->getEnvironment());
        $middlewareCacheWarmer->warmUp($this->app->getCacheDir());
    }

    private function hasCacheFile(): bool
    {
        return file_exists($this->cacheFile);
    }

    private function loadCacheFile(): void
    {
        /** @var array $middlewares */
        $middlewares = include $this->cacheFile;

        foreach ($middlewares as $middleware) {
            $this->push($middleware);
        }
    }

    private function push(string $middlewareClass): void
    {
        try {
            $reflection = new ReflectionClass($middlewareClass);
            $priority   = $reflection->getConstant('PRIORITY');

            $this->specs->insert($reflection, $priority);
        } catch (ReflectionException $e) {
            /* If there's an error getting the kernel class, it's
             * an invalid middleware. If it's invalid, don't push
             * it to the stack
             */
        }
    }
}
