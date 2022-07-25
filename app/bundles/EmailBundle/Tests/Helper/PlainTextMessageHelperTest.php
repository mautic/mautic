<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\PlainTextMessageHelper;

/**
 * Class MessageHelperTest.
 */
class PlainTextMessageHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPlainTextMessage()
    {
        $message = new \Swift_Message('Subject', 'My Content', 'text/html');
        $message->addPart('plain text', 'text/plain');

        $this->assertSame('plain text', PlainTextMessageHelper::getPlainTextFromMessage($message));
    }
}
