<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Organizer;

use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer;

class MailboxOrganizerTest extends \PHPUnit\Framework\TestCase
{
    protected $mailboxes = [
        'EmailBundle_bounces' => [
            'address'           => 'bounces@test.com',
            'host'              => 'mail.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => 0,
            'folder'            => 'INBOX',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
        'EmailBundle_unsubscribes' => [
            'address'           => 'unsubscribes@test.com',
            'host'              => 'mail2.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => 0,
            'folder'            => 'INBOX',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
        'EmailBundle_replies' => [
            'address'           => 'replies@test.com',
            'host'              => 'mail3.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => 0,
            'folder'            => 'INBOX',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
    ];

    /**
     * @testdox Multiple mailboxes with the same imap path should be converted to a single container
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::organize
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainer
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainers
     */
    public function testMailboxesAreConvertedIntoASingleContainer(): void
    {
        $configs   = $this->getConfigs($this->mailboxes);
        $event     = new ParseEmailEvent();
        $organizer = new MailboxOrganizer($event, $configs);
        $organizer->organize();

        $containers = $organizer->getContainers();

        $this->assertCount(1, $containers);
        $key = '{mail.test.com:993/imap/ssl}_user';
        $this->assertArrayHasKey($key, $containers);
    }

    /**
     * @testdox Multiple mailboxes with multiple imap paths are converted to a multiple container
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::organize
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainer
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainers
     */
    public function testMailboxesWithDifferentPathsAreConvertedIntoMultipleContainers(): void
    {
        $mailboxes = [
            'EmailBundle_bounces' => [
                'address'           => 'bounces@test.com',
                'host'              => 'mail.test.com',
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => 'user',
                'password'          => 'password',
                'override_settings' => 0,
                'folder'            => 'INBOX',
                'imap_path'         => '{mail.test.com:993/imap/ssl}',
            ],
            'EmailBundle_unsubscribes' => [
                'address'           => 'unsubscribes@test.com',
                'host'              => 'mail2.test.com',
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => 'user',
                'password'          => 'password',
                'override_settings' => 0,
                'folder'            => 'INBOX',
                'imap_path'         => '{mail2.test.com:993/imap/ssl}',
            ],
            'EmailBundle_replies' => [
                'address'           => 'replies@test.com',
                'host'              => 'mail3.test.com',
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => 'user',
                'password'          => 'password',
                'override_settings' => 0,
                'folder'            => 'INBOX',
                'imap_path'         => '{mail3.test.com:993/imap/ssl}',
            ],
        ];

        $configs   = $this->getConfigs($mailboxes);
        $event     = new ParseEmailEvent();
        $organizer = new MailboxOrganizer($event, $configs);
        $organizer->organize();

        $containers = $organizer->getContainers();

        $this->assertCount(3, $containers);
    }

    /**
     * @testdox Different criteria should be handled by the single container
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::organize
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainer
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainers
     * @covers  \Mautic\EmailBundle\Event\ParseEmailEvent::setCriteriaRequest
     * @covers  \Mautic\EmailBundle\Event\ParseEmailEvent::getCriteriaRequests
     */
    public function testMailboxesWithDifferentCriteriaAreAddedToContainer(): void
    {
        $configs = $this->getConfigs($this->mailboxes);
        $event   = new ParseEmailEvent();
        $event->setCriteriaRequest('EmailBundle', 'replies', Mailbox::CRITERIA_UID.' 1234:*');
        $organizer = new MailboxOrganizer($event, $configs);
        $organizer->organize();

        $containers = $organizer->getContainers();
        $this->assertCount(1, $containers);
        $key      = '{mail.test.com:993/imap/ssl}_user';
        $criteria = $containers[$key]->getCriteria();
        $this->assertEquals(
            [
                Mailbox::CRITERIA_UNSEEN => [
                    'EmailBundle_bounces',
                    'EmailBundle_unsubscribes',
                ],
                Mailbox::CRITERIA_UID.' 1234:*' => [
                    'EmailBundle_replies',
                ],
            ],
            $criteria
        );
    }

    /**
     * @testdox All getters return appropriate values
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::organize
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainer
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxOrganizer::getContainers
     * @covers  \Mautic\EmailBundle\Event\ParseEmailEvent::setCriteriaRequest
     * @covers  \Mautic\EmailBundle\Event\ParseEmailEvent::getCriteriaRequests
     * @covers  \Mautic\EmailBundle\Event\ParseEmailEvent::getMarkAsSeenInstructions
     */
    public function testMailboxesWithDifferentCriteriaWithUnseenFlagMarksContainer(): void
    {
        $configs = $this->getConfigs($this->mailboxes);
        $event   = new ParseEmailEvent();
        $event->setCriteriaRequest('EmailBundle', 'replies', Mailbox::CRITERIA_UID.' 1234:*', true);
        $organizer = new MailboxOrganizer($event, $configs);
        $organizer->organize();

        $containers = $organizer->getContainers();
        $this->assertCount(1, $containers);
        $key = '{mail.test.com:993/imap/ssl}_user';

        $this->assertTrue($containers[$key]->shouldMarkAsSeen());
    }

    /**
     * @return array
     */
    protected function getConfigs($mailboxes)
    {
        $configs = [];

        foreach ($mailboxes as $mailbox => $config) {
            $configs[$mailbox] = new ConfigAccessor($config);
        }

        return $configs;
    }
}
