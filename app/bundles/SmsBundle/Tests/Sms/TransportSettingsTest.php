<?php
/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Tests\Sms;

use Exception;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Integration\Twilio\TwilioTransport;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\Sms\TransportInterface;
use Mautic\SmsBundle\Sms\TransportSettings;
use Mautic\SmsBundle\Sms\TransportSettingsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

class TransportSettingsTest extends \PHPUnit\Framework\TestCase
{
    public function testHasSettingEnabled()
    {
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendSms'])
            ->addMethods(['enabledSettings'])
            ->getMock();
        $transport->method('enabledSettings')->willReturn([TransportSettingsInterface::STAT_DELIVERED, TransportSettingsInterface::STAT_READ]);

        $transportSettings = new TransportSettings($transport);

        $this->assertTrue($transportSettings->hasSetting(TransportSettingsInterface::STAT_DELIVERED));
        $this->assertTrue($transportSettings->hasSetting(TransportSettingsInterface::STAT_READ));
        $this->assertFalse($transportSettings->hasSetting(TransportSettingsInterface::STAT_FAILED));
    }
}
