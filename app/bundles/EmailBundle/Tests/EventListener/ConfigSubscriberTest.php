<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

final class ConfigSubscriberTest extends TestCase
{
    public function testRestoredMonitoredEmailPasswordsKeepTheirOriginalConfigurationValue(): void
    {
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
        $event->setOriginalNormData([
            'emailconfig' => [
                'parameters' => [
                    'monitored_email' => [
                        'general' => [
                            'password' => 'abc%%def%%ghi',
                        ],
                        'EmailBundle_bounces' => [
                            'password' => '%kernel.project_dir%/mail',
                        ],
                    ],
                ],
            ],
        ]);

        (new ConfigSubscriber($this->createStub(CoreParametersHelper::class)))->onConfigBeforeSave($event);

        $monitoredEmail = $event->getConfig('emailconfig')['monitored_email'];

        $this->assertSame('abc%%def%%ghi', $monitoredEmail['general']['password']);
        $this->assertSame('%kernel.project_dir%/mail', $monitoredEmail['EmailBundle_bounces']['password']);
    }
    public function testRestoredMonitoredEmailPasswordsUsePersistedConfigurationWithoutSnapshot(): void
    {
        $event = new ConfigEvent([
            'emailconfig' => [
                'monitored_email' => [
                    'general' => [
                        'password' => '',
                    ],
                ],
            ],
        ], new ParameterBag());
        $helper = $this->createMock(CoreParametersHelper::class);
        $helper->expects($this->once())
            ->method('get')
            ->with('monitored_email', [])
            ->willReturn([
                'general' => [
                    'password' => 'persisted-password',
                ],
            ]);

        (new ConfigSubscriber($helper))->onConfigBeforeSave($event);

        $this->assertSame(
            'persisted-password',
            $event->getConfig('emailconfig')['monitored_email']['general']['password']
        );
    }
}
