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
     * @var AppKernel
     */
    private $app;

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var SplPriorityQueue|ReflectionClass[]
     */
    private $specs;

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
        if ($this->hasCacheFile()) {
            $this->loadCacheFile();

            return;
        }

        $this->loadFromDirectory(__DIR__);

        $env    = $this->app->getEnvironment();
        $envDir = __DIR__.'/'.ucfirst($env);

        if (file_exists($envDir)) {
            $this->loadFromDirectory($envDir, $env);
        }

        $this->createCacheFile();
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

    private function createCacheFile(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        if (false === file_exists($this->app->getCacheDir())) {
            mkdir($this->app->getCacheDir(), 0755, true);
        }

        $data  = [];
        $clone = clone $this->specs;
        $clone->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        /** @var ReflectionClass $middleware */
        foreach ($clone as $middleware) {
            $data[] = $middleware->getName();
        }

        $content = sprintf('<?php return %s;', var_export($data, true));

        file_put_contents($this->cacheFile, $content);
    }

    private function loadFromDirectory(string $directory, ?string $env = null): void
    {
        $glob = glob($directory.'/*Middleware.php');

        if (!empty($glob)) {
            $this->addMiddlewares($glob, $env);
        }
    }

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
