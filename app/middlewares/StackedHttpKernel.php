<?php

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Provides a stacked HTTP kernel.
 *
 * Copied from https://github.com/stackphp/builder/ with added compatibility
 * for Symfony 6.
 *
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21StackMiddleware%21StackedHttpKernel.php/class/StackedHttpKernel/11.x
 * @see \Drupal\Core\DependencyInjection\Compiler\StackedKernelPass
 */
class StackedHttpKernel implements HttpKernelInterface, TerminableInterface
{
    /**
     * The decorated kernel.
     *
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * A set of middlewares that are wrapped around this kernel.
     *
     * @var array
     */
    private $middlewares = [];

    /**
     * Constructs a stacked HTTP kernel.
     *
     * @param HttpKernelInterface $kernel
     *                                         The decorated kernel
     * @param array               $middlewares
     *                                         An array of previous middleware services
     */
    public function __construct(HttpKernelInterface $kernel, array $middlewares)
    {
        $this->kernel      = $kernel;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MAIN_REQUEST, $catch = true): Response
    {
        return $this->kernel
            ->handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response): void
    {
        $previous = null;
        foreach ($this->middlewares as $kernel) {
            // If the previous kernel was terminable we can assume this middleware
            // has already been called.
            if (!$previous instanceof TerminableInterface && $kernel instanceof TerminableInterface) {
                $kernel->terminate($request, $response);
            }
            $previous = $kernel;
        }
    }
}
