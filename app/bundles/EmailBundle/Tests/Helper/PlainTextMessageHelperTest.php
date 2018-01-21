<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;

/**
 * Class MessageHelperTest.
 */
class PlainTextMessageHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPlainTextMessage()
    {
        $message = new \Swift_Message('Subject', 'My Content', 'text/html');
        $message->addPart('plain text', 'text/plain');

        $this->assertSame('plain text', PlainTextMassageHelper::getPlainTextFromMessage($message));
    }
}
