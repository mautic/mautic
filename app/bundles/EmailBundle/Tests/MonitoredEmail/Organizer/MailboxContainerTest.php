<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Organizer;

use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Organizer\MailboxContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(ConfigAccessor::class)]
#[CoversClass(MailboxContainer::class)]
final class MailboxContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array<string, string>
     */
    private array $config = [
        'imap_path' => 'path',
        'user'      => 'user',
        'host'      => 'host',
        'folder'    => 'folder',
    ];

    #[TestDox('Container\'s path should be config\'s path for services that don\'t have access
         to the config but need to set the path')]
    public function testPathMatches(): void
    {
        $configAccessor   = new ConfigAccessor($this->config);
        $mailboxContainer = new MailboxContainer($configAccessor);

        $this->assertEquals($configAccessor->getPath(), $mailboxContainer->getPath());
    }

    #[TestDox('Criteria should be returned correctly')]
    public function testCriteriaIsSetAsExpected(): void
    {
        $configAccessor   = new ConfigAccessor($this->config);
        $mailboxContainer = new MailboxContainer($configAccessor);

        $criteria = [
            Mailbox::CRITERIA_ALL => [
                'mailbox1',
                'mailbox2',
            ],
            Mailbox::CRITERIA_UNANSWERED => [
                'mailbox2',
            ],
        ];

        $mailboxContainer->addCriteria(Mailbox::CRITERIA_ALL, 'mailbox1');
        $mailboxContainer->addCriteria(Mailbox::CRITERIA_ALL, 'mailbox2');
        $mailboxContainer->addCriteria(Mailbox::CRITERIA_UNANSWERED, 'mailbox2');

        $this->assertEquals($criteria, $mailboxContainer->getCriteria());
    }

    #[TestDox('Keep as unseen flag should be correctly returned when set')]
    public function testUnseenFlagIsReturnedAsExpected(): void
    {
        $configAccessor   = new ConfigAccessor($this->config);
        $mailboxContainer = new MailboxContainer($configAccessor);

        $mailboxContainer->keepAsUnseen();

        $this->assertFalse($mailboxContainer->shouldMarkAsSeen());
    }
}
