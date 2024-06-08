<?php

namespace Mautic\MessengerBundle\Tests\Message;

use Mautic\MessengerBundle\Message\PageHitNotification;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PageHitNotificationTest extends TestCase
{
    public function testConstruct(): void
    {
        $request = new Request();
        $request->query->set('testMe', 'Hit me once');

        $message = new PageHitNotification(78, $request, false, false, 3, 1);

        $this->assertArrayHasKey('testMe', $message->getRequest()->query->all());
        $this->assertEquals($request, $message->getRequest());
    }
}
