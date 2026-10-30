<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\ConfigBundleEvent\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

final class ConfigSubscriberTest extends TestCase
{
    public function testRestoredMonitoredEmailPasswordsAreEscapedBeforeSaving(): void
    {
        $coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->with('monitored_email')->willReturn([
            'general' => [
                'password' => 'abc%def%ghi',
            ],
            'EmailBundle_bounces' => [
                'password' => 'bounce%password',
            ],
        ]);

        $event = new ConfigEvent([
            'emailconfig' => [
                'monitored_email' => [
                    'general' => [
                        'password' => '',
                    ],
                    'EmailBundle_bounces' => [
                        'password' => '',
                        'host'     => 'imap.example.invalid',
                        'address'  => 'bounce@example.invalid',
                        'folder'   => 'INBOX',
                    ],
                ],
            ],
        ], new ParameterBag());

        (new ConfigSubscriber($coreParametersHelper))->onConfigBeforeSave($event);

        $monitoredEmail = $event->getConfig('emailconfig')['monitored_email'];

        self::assertSame('abc%%def%%ghi', $monitoredEmail['general']['password']);
        self::assertSame('bounce%%password', $monitoredEmail['EmailBundle_bounces']['password']);
    }
}
