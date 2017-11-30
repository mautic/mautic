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

use Stack\StackedHttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MiddlewareBuilder
{
    protected $specs;

    public function __construct($env = null)
    {
        $this->specs = new \SplPriorityQueue();

        $middlewares = glob(__DIR__.'/*Middleware.php');

        $this->addMiddlewares($middlewares);

        if (isset($env)) {
            $envMiddlewares = glob(__DIR__.'/'.ucfirst($env).'/*Middleware.php');

            if (!empty($envMiddlewares)) {
                $this->addMiddlewares($envMiddlewares, $env);
            }
        }
    }

    public function addMiddlewares(array $middlewares, $env = null)
    {
        $prefix = 'Mautic\\Middleware\\';

        if ($env) {
            $prefix .= ucfirst($env).'\\';
        }

        foreach ($middlewares as $middleware) {
            $this->push($prefix.basename(substr($middleware, 0, -4)));
        }
    }

    public function push($kernelClass)
    {
        $reflection = new \ReflectionClass($kernelClass);
        $priority   = $reflection->getConstant('PRIORITY');

        $this->specs->insert($reflection, $priority);
    }

    public function resolve(HttpKernelInterface $app)
    {
        $middlewares = [$app];

        /** @var \ReflectionClass $spec */
        foreach ($this->specs as $spec) {
            $app = $spec->newInstanceArgs([$app]);

            array_unshift($middlewares, $app);
        }

        return new StackedHttpKernel($app, $middlewares);
    }
}
