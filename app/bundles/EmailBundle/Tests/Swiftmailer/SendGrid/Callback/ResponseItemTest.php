<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\ResponseItem;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Exception\ResponseItemException;
use Mautic\LeadBundle\Entity\DoNotContact;

class ResponseItemTest extends \PHPUnit\Framework\TestCase
{
    public function testFullResponseItem()
    {
        $item = [
            'email'  => 'info@example.com',
            'reason' => 'My reason',
            'event'  => 'bounce',
            'mautic_metadata' => 'a:1:{s:16:"example@test.com";a:1:{s:7:"emailId";i:1;}}',
        ];

        $responseItem = new ResponseItem($item);

        $this->assertSame('info@example.com', $responseItem->getEmail());
        $this->assertSame('My reason', $responseItem->getReason());
        $this->assertSame(DoNotContact::BOUNCED, $responseItem->getDncReason());
        $this->assertSame(1, $responseItem->getChannel());
    }

    public function testResponseItemWithoutReason()
    {
        $item = [
            'email'  => 'info@example.com',
            'event'  => 'spamreport',
        ];

        $responseItem = new ResponseItem($item);

        $this->assertSame('info@example.com', $responseItem->getEmail());
        $this->assertNull($responseItem->getReason());
        $this->assertSame(DoNotContact::BOUNCED, $responseItem->getDncReason());
        $this->assertNull($responseItem->getChannel());
    }

    public function testResponseItemWithoutEmail()
    {
        $item = [
            'event'  => 'spamreport',
        ];

        $this->expectException(ResponseItemException::class);
        new ResponseItem($item);
    }
}
