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

use Mautic\EmailBundle\Helper\MessageHelper;

/**
 * Class MessageHelperTest.
 */
class MessageHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testHostNotFoundDsnReport()
    {
        $dsnReport = <<<'DSN'
Original-Recipient: sdfgsdfg@seznan.cz
Final-Recipient: rfc822;sdfgsdfg@seznan.cz
Action: failed
Status: 5.4.4
Diagnostic-Code: DNS; Host not found
DSN;

        $result = MessageHelper::parseDsn(null, $dsnReport);

        $this->assertEquals('dns_unknown', $result['rule_cat']);
    }

    public function testRelayingDsnReport()
    {

        $dsnReport = <<<'DSN'
Original-Recipient: sdfgsdfg@seznan.cz
Final-Recipient: rfc822;sdfgsdfg@seznan.cz
Action: failed
Status: 5.4.4
Diagnostic-Code: DNS; Host not found
DSN;
        $result = MessageHelper::parseDsn(null, $dsnReport);
        $this->assertEquals('dns_unknown', $result['rule_cat']);

        $dsnReport = <<<'DSN'
Final-Recipient: rfc822; user@domain.co.uk
Action: failed
Status: 5.0.0
Remote-MTA: dns; mx.zoho.com. ({ip-address-B}, the server for the domain domain.co.uk.)
Diagnostic-Code: smtp; 553 Relaying disallowed
DSN;

        $result = MessageHelper::parseDsn(null, $dsnReport);

        $this->assertEquals('unknown', $result['rule_cat']);
    }

    public function testUserNotFoundReport()
    {
        $bodyReport = <<<'BODY'
<myuser@example.net>:
Remote host said:
550 5.1.1 User unknown
BODY;

        $result = MessageHelper::parseBody($bodyReport, "name@domain.here");

        $this->assertEquals('unknown', $result['rule_cat']);

    }

    public function bodyLinesProvider ()
    {
        return [
            "mailbox full-1" => ["full", "554 5.2.2 <user@domain.fr>: Recipient address rejected: Quota exceeded (mailbox for user is full)"],
            "mailbox full-2" => ["full", "The email account that you tried to reach is over quota."],
            "mailbox full-3" => ["full", "452 4.2.2 <user@domain.com> Mailbox is full / Blocks limit exceeded / Inode limit exceeded"],
            "unknown-1" => ["unknown", "550 5.1.1 The email account that you tried to reach does not exist."],
            "spam-1" => ["antispam", "550-5.7.1 [193.198.1.11] Our system has detected that this message is\r550-5.7.1 likely unsolicited mail."]
        ];
    }
    /**
     * @dataProvider bodyLinesProvider
     */
    public function testBodyLines($expected, $bodyLine)
    {
        $result = MessageHelper::parseBody($bodyLine, "name@domain.name");

        $this->assertEquals($expected, $result['rule_cat']);
    }

}