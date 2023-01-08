<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Reply;

use Mautic\EmailBundle\MonitoredEmail\Exception\ReplyNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply\Parser;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply\RepliedEmail;

class ParserTest extends \PHPUnit\Framework\TestCase
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
     * @testdox Test that an email is found inside a feedback report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Reply\Parser::parse()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Reply\RepliedEmail::getStatHash()
     */
    public function testThatReplyIsDetectedThroughTrackingPixelWithUnsubcribeLink()
    {
        $message           = new Message();
        $message->textHtml = <<<'BODY'
<html><head><meta http-equiv="Content-Type" content="text/html; charset=us-ascii"></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; line-break: after-white-space;" class=""><br class=""><div><br class=""><blockquote type="cite" class=""><div class="">On Mar 13, 2019, at 16:31, Alan Hartless &lt;<a href="mailto:email@test.com" class="">email@test.com</a>&gt; wrote:</div><br class="Apple-interchange-newline"><div class=""><div data-section-wrapper="1" style="caret-color: rgb(0, 0, 0); font-family: Helvetica; font-size: 12px; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;" class=""><center class=""><table data-section="1" width="600" cellpadding="0" cellspacing="0" class=""><tbody class=""><tr class=""><td class=""><div data-slot-container="1" style="min-height: 30px;" class=""><div data-slot="text" class=""><br class=""><h2 class="">Hello there!</h2><br class="">We haven\'t heard from you for a while...<span class="Apple-converted-space">&nbsp;</span><br class=""><br class=""><a href="http://test.com/email/unsubscribe/5c897694957a7581067884" class="">Unsubscribe</a><span class="Apple-converted-space">&nbsp;</span>to no longer receive emails from us. |<span class="Apple-converted-space">&nbsp;</span><a href="http://test.com/email/view/5c897694957a7581067884" class="">Having trouble reading this email? Click here.</a><span class="Apple-converted-space">&nbsp;</span><br class=""></div></div></td></tr></tbody></table></center></div><img height="1" width="1" src="http://test.com/email/5c897694957a7581067884.gif" alt="" style="caret-color: rgb(0, 0, 0); font-family: Helvetica; font-size: 12px; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;" class=""><span style="caret-color: rgb(0, 0, 0); font-family: Helvetica; font-size: 12px; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none; float: none; display: inline !important;" class=""><span class="Apple-converted-space">&nbsp;</span></span><img border="0" width="1" height="1" alt="" src="http://clicks.test.com/q/OPxGtVSJgAVhgr5j-nfhoQ~~/AACuKQA~/RgRea_uVPlcDc3BjQgoAJJV2iVydE4kNUhNjb250YWN0QGFsYW4uZGlyZWN0WAQAAABv" style="caret-color: rgb(0, 0, 0); font-family: Helvetica; font-size: 12px; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;" class=""></div></blockquote></div><br class=""></body></html>
BODY;

        $parser = new Parser($message);

        $replyEmail = $parser->parse();
        $this->assertInstanceOf(RepliedEmail::class, $replyEmail);

        $this->assertEquals('5c897694957a7581067884', $replyEmail->getStatHash());
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
