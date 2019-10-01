<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\Notification\Handler;

use MauticPlugin\IntegrationsBundle\Sync\Exception\HandlerNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Handler\HandlerContainer;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Handler\HandlerInterface;

class HandlerContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionThrownIfIntegrationNotFound(): void
    {
        $this->expectException(HandlerNotSupportedException::class);

        $handler = new HandlerContainer();
        $handler->getHandler('foo', 'bar');
    }

    public function testExceptionThrownIfObjectNotFound(): void
    {
        $this->expectException(HandlerNotSupportedException::class);

        $handler = new HandlerContainer();

        $mockHandler = $this->createMock(HandlerInterface::class);
        $mockHandler->method('getIntegration')
            ->willReturn('foo');
        $mockHandler->method('getSupportedObject')
            ->willReturn('bogus');

        $handler->registerHandler($mockHandler);

        $handler->getHandler('foo', 'bar');
    }

    public function testHandlerIsRegistered(): void
    {
        $handler = new HandlerContainer();

        $mockHandler = $this->createMock(HandlerInterface::class);
        $mockHandler->method('getIntegration')
            ->willReturn('foo');
        $mockHandler->method('getSupportedObject')
            ->willReturn('bar');

        $handler->registerHandler($mockHandler);

        $returnedHandler = $handler->getHandler('foo', 'bar');

        $this->assertEquals($mockHandler, $returnedHandler);
    }
}
