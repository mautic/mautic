<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;

class DoNotContactPartsTest extends \PHPUnit\Framework\TestCase
{
    public function testDncBouncedEmail()
    {
        $doNotContactParts = new DoNotContactParts('dnc_bounced');

        $this->assertSame('email', $doNotContactParts->getChannel());
        $this->assertSame(
            DoNotContact::BOUNCED,
            $doNotContactParts->getParameterType(),
            'Type for dnc_bounced should be bounced'
        );

        $doNotContactParts = new DoNotContactParts('dnc_unsubscribed');

        $this->assertSame('email', $doNotContactParts->getChannel());
        $this->assertSame(
            DoNotContact::UNSUBSCRIBED,
            $doNotContactParts->getParameterType(),
            'Type for dnc_unsubscribed should be unsubscribed'
        );

        $doNotContactParts = new DoNotContactParts('dnc_unsubscribed_manually');

        $this->assertSame('email', $doNotContactParts->getChannel());
        $this->assertSame(
            DoNotContact::MANUAL,
            $doNotContactParts->getParameterType(),
            'Type for dnc_manual should be manual'
        );
    }

    public function testDncBouncedSms()
    {
        $doNotContactParts = new DoNotContactParts('dnc_bounced_sms');

        $this->assertSame('sms', $doNotContactParts->getChannel());
        $this->assertSame(
            DoNotContact::BOUNCED,
            $doNotContactParts->getParameterType(),
            'Type for dnc_bounced_sms should be bounced'
        );

        $doNotContactParts = new DoNotContactParts('dnc_unsubscribed_sms');

        $this->assertSame('sms', $doNotContactParts->getChannel());
        $this->assertSame(
            DoNotContact::UNSUBSCRIBED,
            $doNotContactParts->getParameterType(),
            'Type for dnc_unsubscribed_sms should be unsubscribed'
        );

        $doNotContactParts = new DoNotContactParts('dnc_unsubscribed_sms_manually');

        $this->assertSame('sms', $doNotContactParts->getChannel());
        $this->assertSame(
            DoNotContact::MANUAL,
            $doNotContactParts->getParameterType(),
            'Type for dnc_unsubscribed_sms_manually should be manual'
        );
    }
}
