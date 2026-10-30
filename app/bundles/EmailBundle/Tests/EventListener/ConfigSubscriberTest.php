<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\EmailBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

final class ConfigSubscriberTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function passwordCases(): iterable
    {
        yield 'restored general password containing percent signs' => [
            'general',
            '',
            'abc%def%ghi',
            'abc%%def%%ghi',
        ];
        yield 'restored password containing an allowed parameter name' => [
            'general',
            '',
            'abc%kernel.project_dir%xyz',
            'abc%%kernel.project_dir%%xyz',
        ];
        yield 'restored bounce password containing percent signs' => [
            'EmailBundle_bounces',
            '',
            'abc%def%ghi',
            'abc%%def%%ghi',
        ];
        yield 'restored unsubscribe password containing percent signs' => [
            'EmailBundle_unsubscribes',
            '',
            'abc%def%ghi',
            'abc%%def%%ghi',
        ];

        yield 'new password already escaped by the form' => [
            'general',
            'new%%password',
            'old%password',
            'new%%password',
        ];

        yield 'restored password without percent signs' => [
            'general',
            '',
            'ordinary-password',
            'ordinary-password',
        ];
    }

    #[DataProvider('passwordCases')]
    public function testMonitoredEmailPasswordPreservation(
        string $mailbox,
        string $submittedPassword,
        string $storedPassword,
        string $expectedPassword,
    ): void {
        $event = new ConfigEvent([
            'emailconfig' => [
                'monitored_email' => [
                    $mailbox => [
                        'password' => $submittedPassword,
                        'host' => 'imap.example.test',
                        'address' => 'mailbox@example.test',
                        'folder' => 'INBOX',
                    ],
                ],
            ],
        ], new ParameterBag());

        $event->setOriginalModelData([
            'emailconfig' => [
                'monitored_email' => [
                    $mailbox => [
                        'password' => '' === $submittedPassword
                            ? $expectedPassword
                            : $storedPassword,
                    ],
                ],
            ],
        ]);

        $subscriber = new ConfigSubscriber();
        $subscriber->onConfigBeforeSave($event);

        self::assertSame(
            $expectedPassword,
            $event->getConfig('emailconfig')['monitored_email'][$mailbox]['password']
        );
    }
}
