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
}
