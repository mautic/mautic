<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\ResponseItem;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Exception\ResponseItemException;
use Mautic\LeadBundle\Entity\DoNotContact;

class ResponseItemTest extends \PHPUnit_Framework_TestCase
{
    public function testFullResponseItem()
    {
        $item = [
            'email'  => 'info@example.com',
            'reason' => 'My reason',
            'event'  => 'bounce',
        ];

        $responseItem = new ResponseItem($item);

        $this->assertSame('info@example.com', $responseItem->getEmail());
        $this->assertSame('My reason', $responseItem->getReason());
        $this->assertSame(DoNotContact::BOUNCED, $responseItem->getDncReason());
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
