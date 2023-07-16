<?php

namespace Mautic\MessengerBundle\Tests\Message;

use Mautic\MessengerBundle\Message\EmailHitNotification;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EmailHitNotificationTest extends TestCase
{
    public function testConstruct(): void
    {
        $request = new Request();
        $request->query->set('testMe', 'Hit me once');

        $message = new EmailHitNotification('statid', $request);

        $this->assertArrayHasKey('testMe', $message->getRequest()->query->all());
        $this->assertEquals($request, $message->getRequest());
    }
}
