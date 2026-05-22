<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;

class ExceptionListenerTest extends TestCase
{
    /**
     * Regression test for https://github.com/mautic/mautic/issues/15952.
     *
     * When the sub-request used to render an error page throws an Error
     * (e.g. \TypeError, or Symfony's FatalError wrapping a parse error),
     * the previous catch (\Exception $e) block missed it entirely, so the
     * Error propagated up. After widening to catch (\Throwable $e), the
     * Error reaches the chain-traversal + reflection block, which used to
     * use ReflectionProperty('Exception', 'previous'). Setting that on an
     * Error instance triggers a fatal:
     *
     *   Cannot access private property Error::$previous
     *
     * because Error declares its own private $previous, separate from
     * Exception's. The fix picks Error::class or Exception::class for the
     * reflection target based on the wrapper's base class so $previous can
     * be assigned on either branch of the Throwable hierarchy.
     */
    public function testErrorFromSubRequestIsRethrownWithoutFatal(): void
    {
        $originalException = new \RuntimeException('original kernel exception');
        $subRequestError   = new \TypeError('sub-request blew up');

        $router = $this->createMock(Router::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel->method('handle')->willThrowException($subRequestError);

        $listener = new ExceptionListener($router, null);

        $event = new ExceptionEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $originalException
        );

        $caught = null;
        try {
            $listener->onKernelException($event);
        } catch (\Throwable $e) {
            $caught = $e;
        }

        // Before the fix:
        //   - catch (\Exception $e) didn't match \TypeError → TypeError leaked
        //     out unchanged; OR if it did reach the reflection line via the
        //     chain, "Cannot access private property Error::$previous" surfaced.
        // After the fix:
        //   - the TypeError is caught, the original exception is chained as its
        //     ->previous, and the TypeError is re-thrown.
        self::assertSame(
            $subRequestError,
            $caught,
            'ExceptionListener must catch the sub-request Throwable and re-throw it.'
        );
        self::assertSame(
            $originalException,
            $caught->getPrevious(),
            'The original exception must be chained as ->previous of the rethrown Throwable.'
        );
    }

    /**
     * Companion case: a chained Exception whose deepest ->previous is an
     * \Error. The traversal walks to the Error, and reflection on
     * Exception's $previous would fail because Error has its own private
     * $previous; the fix picks Error::class on this branch.
     */
    public function testReflectionWorksWhenDeepestPreviousIsAnError(): void
    {
        $originalException   = new \RuntimeException('original');
        $deepestError        = new \TypeError('deepest');
        $subRequestException = new \RuntimeException('sub-request', 0, $deepestError);

        $router = $this->createMock(Router::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel->method('handle')->willThrowException($subRequestException);

        $listener = new ExceptionListener($router, null);

        $event = new ExceptionEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $originalException
        );

        try {
            $listener->onKernelException($event);
            self::fail('Listener was expected to re-throw the sub-request exception.');
        } catch (\Throwable $rethrown) {
            self::assertSame($subRequestException, $rethrown);
            // The original exception is chained on the deepest Throwable.
            self::assertSame($originalException, $deepestError->getPrevious());
        }
    }
}
