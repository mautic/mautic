<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class MiddlewareCacheWarmer implements CacheWarmerInterface
{
    private ?string $cacheFile = null;

    /**
     * @var \SplPriorityQueue|\ReflectionClass[]
     */
    private \SplPriorityQueue $specs;

    public function __construct(
        private string $env
    ) {
        $this->specs     = new \SplPriorityQueue();
    }

    /**
     * @inerhitDoc
     */
    public function warmUp(string $cacheDirectory)
    {
        $this->cacheFile = sprintf('%s/middlewares.cache.php', $cacheDirectory);
        $this->createCacheFile($cacheDirectory);

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function createCacheFile($cacheDirectory): void
    {
        $middlewarsDir = __DIR__.'/../../../middlewares';

        $this->loadFromDirectory($middlewarsDir);

        $envDir = $middlewarsDir.'/'.ucfirst($this->env);

        if (file_exists($envDir)) {
            $this->loadFromDirectory($envDir, $this->env);
        }

        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        if (false === file_exists($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        $data  = [];
        $this->specs->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

        /** @var \ReflectionClass $middleware */
        foreach ($this->specs as $middleware) {
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

    private function addMiddlewares(array $middlewares, ?string $env = null): void
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
            $reflection = new \ReflectionClass($middlewareClass);
            $priority   = $reflection->getConstant('PRIORITY');

            $this->specs->insert($reflection, $priority);
        } catch (\ReflectionException) {
            /* If there's an error getting the kernel class, it's
             * an invalid middleware. If it's invalid, don't push
             * it to the stack
             */
        }
    }
}
