<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Exception;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ApiErrorExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShortMessage(): void
    {
        $apiErrorException = new ApiErrorException('Main Error message.');
        $this->assertEmpty($apiErrorException->getShortMessage());

        $shortMessage = 'This is short message';
        $apiErrorException->setShortMessage($shortMessage);
        $this->assertSame($shortMessage, $apiErrorException->getShortMessage());
    }
}
