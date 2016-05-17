<?php

/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Middleware;

use Stack\StackedHttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MiddlewareBuilder
{
    protected $specs;
    
    public function __construct()
    {
        $this->specs = new \SplPriorityQueue();

        $middlewares = glob(__DIR__ . '/*Middleware.php');

        foreach ($middlewares as $middleware) {
            $this->push('Mautic\\Middleware\\' . basename(substr($middleware, 0, -4)));
        }
    }
    
    public function push($kernelClass)
    {
        $reflection = new \ReflectionClass($kernelClass);
        $priority = $reflection->getConstant('PRIORITY');

        $this->specs->insert($reflection, $priority);
    }

    public function resolve(HttpKernelInterface $app)
    {
        $middlewares = array($app);
        
        /** @var \ReflectionClass $spec */
        foreach ($this->specs as $spec) {
            $app = $spec->newInstanceArgs([$app]);
            
            array_unshift($middlewares, $app);
        }
        
        return new StackedHttpKernel($app, $middlewares);
    }
}
