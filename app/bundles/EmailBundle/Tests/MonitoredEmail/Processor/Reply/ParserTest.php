<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Reply;

use Mautic\EmailBundle\MonitoredEmail\Exception\ReplyNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply\Parser;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply\RepliedEmail;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that an email is found inside a feedback report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Reply\Parser::parse()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Reply\RepliedEmail::getStatHash()
     */
    public function testThatReplyIsDetectedThroughTrackingPixel()
    {
        $message           = new Message();
        $message->textHtml = <<<'BODY'
<img src="http://test.com/email/123abc.gif" />
BODY;

        $parser = new Parser($message);

        $replyEmail = $parser->parse();
        $this->assertInstanceOf(RepliedEmail::class, $replyEmail);

        $this->assertEquals('123abc', $replyEmail->getStatHash());
    }

    /**
     * @testdox Test that an exeption is thrown if the hash is not found
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Reply\Parser::parse()
     */
    public function testExceptionIsThrownWithHashNotFound()
    {
        $this->expectException(ReplyNotFound::class);

        $message = new Message();
        $parser  = new Parser($message);

        $parser->parse();
    }
}
