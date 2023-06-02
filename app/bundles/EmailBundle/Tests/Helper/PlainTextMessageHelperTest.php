<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use Symfony\Component\Mime\Email;

/**
 * Class MessageHelperTest.
 */
class PlainTextMessageHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPlainTextMessage()
    {
        $message = (new Email())
        ->subject('Subject')
        ->html('My Content')
        ->text('plain text');

        $this->assertSame('plain text', PlainTextMessageHelper::getPlainTextFromMessage($message));
    }
}
