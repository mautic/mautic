<?php

namespace Mautic\Middleware;

use AppKernel;
use Mautic\CoreBundle\Cache\MiddlewareCacheWarmer;
use ReflectionClass;
use ReflectionException;
use SplPriorityQueue;
use Stack\StackedHttpKernel;

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
