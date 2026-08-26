<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Entity\MessageQueueRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Frequency rules are configured by the "email_frequency_number" config parameter, which is read
 * during the kernel boot. Therefore each test reboots the kernel with the config it needs.
 * MauticMysqlTestCase forbids re-creating the client while transaction rollback cleanup
 * is active, so we fall back to resetDatabase() for cleanup instead.
 */
final class EmailModelFrequencyRuleFunctionalTest extends MauticMysqlTestCase
{
    private const int EMAILS_A_MONTH = 2;

    protected $useCleanupRollback = false;

    private EmailModel $emailModel;

    /**
     * @return iterable<string, array{bool}>
     */
    public static function dataFrequencyRules(): iterable
    {
        yield 'Custom Frequency Rules' => [false];
        yield 'Default Frequency Rules' => [true];
    }

    #[DataProvider('dataFrequencyRules')]
    public function testFrequencyRulesAreAppliedWhenSendToDncIsNo(bool $useDefaultFrequencyRules): void
    {
        $this->bootWithFrequencyConfig($useDefaultFrequencyRules);

        $contact = $this->createContact();
        $email   = $this->createTemplateEmail();
        $this->createFrequencyRule($contact, $useDefaultFrequencyRules);
        $this->createEmailStats($email, $contact);
        $this->em->flush();

        $this->sendEmail($email, $contact);
        $this->assertEmailIsPostponed($email, $contact);
    }

    #[DataProvider('dataFrequencyRules')]
    public function testFrequencyRulesAreNotAppliedWhenSendToDncIsTrue(bool $useDefaultFrequencyRules): void
    {
        $this->bootWithFrequencyConfig($useDefaultFrequencyRules);

        $contact = $this->createContact();
        $email   = $this->createTemplateEmail();
        $email->setSendToDnc(true);
        $this->em->persist($email);
        $this->createFrequencyRule($contact, $useDefaultFrequencyRules);
        $this->createEmailStats($email, $contact);
        $this->em->flush();

        $this->sendEmail($email, $contact);
        $this->assertEmailIsNotPostponed();
    }

    #[DataProvider('dataFrequencyRules')]
    public function testEmailsWithSendToDncSetToYesAreNotCountedTowardsFrequencyRules(bool $useDefaultFrequencyRules): void
    {
        $this->bootWithFrequencyConfig($useDefaultFrequencyRules);

        $contact     = $this->createContact();
        $emailToSend = $this->createTemplateEmail();
        $emailDncYes = $this->createTemplateEmail();
        $emailDncYes->setSendToDnc(true);
        $this->em->persist($emailToSend);
        $this->createFrequencyRule($contact, $useDefaultFrequencyRules);
        $this->createEmailStats($emailDncYes, $contact);
        $this->em->flush();

        $this->sendEmail($emailToSend, $contact);
        $this->assertEmailIsNotPostponed();
    }

    /**
     * Reboots the kernel so that CoreParametersHelper picks up the frequency configuration.
     * Default frequency rules are set globally in the configuration, custom ones per contact.
     */
    private function bootWithFrequencyConfig(bool $useDefaultFrequencyRules): void
    {
        $this->setUpSymfony(array_merge($this->configParams, [
            'email_frequency_number' => $useDefaultFrequencyRules ? self::EMAILS_A_MONTH : 0,
            'email_frequency_time'   => 'MONTH',
        ]));

        // Re-authenticate: setUpSymfony() destroys the previous client and its security token.
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $emailModel = self::getContainer()->get(EmailModel::class);
        $this->assertInstanceOf(EmailModel::class, $emailModel);
        $this->emailModel = $emailModel;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.com');
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $this->em->persist($contact);

        return $contact;
    }

    private function createTemplateEmail(): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setSubject('Test');
        $email->setCustomHTML('test EN');
        $email->setEmailType('template');
        $email->setLanguage('en');
        $this->em->persist($email);

        return $email;
    }

    private function createFrequencyRule(Lead $contact, bool $useDefaultFrequencyRules): void
    {
        if ($useDefaultFrequencyRules) {
            return;
        }

        $frequencyRule = new FrequencyRule();
        $frequencyRule->setLead($contact);
        $frequencyRule->setDateAdded(new \DateTime());
        $frequencyRule->setChannel('email');
        $frequencyRule->setFrequencyNumber(self::EMAILS_A_MONTH);
        $frequencyRule->setFrequencyTime('MONTH');
        $this->em->persist($frequencyRule);
    }

    private function createEmailStats(Email $email, Lead $contact): void
    {
        $exceedFrequencyRule = self::EMAILS_A_MONTH + 1;

        for ($i = 0; $i < $exceedFrequencyRule; ++$i) {
            $stat = new Stat();
            $stat->setEmail($email);
            $stat->setLead($contact);
            $stat->setEmailAddress($contact->getEmail());
            $stat->setDateSent(new \DateTime('-1 day'));
            $this->em->persist($stat);
        }
    }

    private function sendEmail(Email $email, Lead $contact): void
    {
        $this->emailModel->sendEmail(
            $email,
            [
                [
                    'id'        => $contact->getId(),
                    'email'     => $contact->getEmail(),
                    'firstname' => $contact->getFirstname(),
                    'lastname'  => $contact->getLastname(),
                ],
            ]
        );
    }

    private function assertEmailIsNotPostponed(): void
    {
        $messageQueueRepository = $this->em->getRepository(MessageQueue::class);
        $this->assertInstanceOf(MessageQueueRepository::class, $messageQueueRepository);

        $this->assertSame(0, $messageQueueRepository->count([]), 'Email should not be postponed.');
    }

    private function assertEmailIsPostponed(Email $email, Lead $contact): void
    {
        $messageQueueRepository = $this->em->getRepository(MessageQueue::class);
        $this->assertInstanceOf(MessageQueueRepository::class, $messageQueueRepository);

        $queuedMessages = $messageQueueRepository->findBy([]);
        $this->assertCount(1, $queuedMessages, 'Email should be postponed.');

        $queuedMessage = reset($queuedMessages);
        $this->assertInstanceOf(MessageQueue::class, $queuedMessage);
        $this->assertSame('email', $queuedMessage->getChannel());
        $this->assertSame($email->getId(), $queuedMessage->getChannelId());
        $this->assertSame($contact, $queuedMessage->getLead());
        $this->assertSame($queuedMessage::STATUS_PENDING, $queuedMessage->getStatus());
    }
}
