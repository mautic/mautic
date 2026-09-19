<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\CopyRepository;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\SMimeHelper;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\Model\SendEmailToContact;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\EmailBundle\Tests\Helper\Transport\AddressLengthEnforcingTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * A display name that pushes the encoded address past mailer_address_length_limit must
 * not leave the contact pending after a tokenized send (#14726). The transport measures
 * the recipient it builds from the message metadata, as the SES plugin does.
 */
final class QueuedSendAddressLengthFunctionalTest extends MauticMysqlTestCase
{
    private const ADDRESS_LENGTH_LIMIT = 30;

    protected function setUp(): void
    {
        $this->configParams['mailer_address_length_limit'] = self::ADDRESS_LENGTH_LIMIT;

        parent::setUp();
    }

    public function testLongDisplayNameDoesNotLeaveTheContactPendingForever(): void
    {
        $contact = new Lead();
        $contact->setEmail('long.name@example.com');
        $contact->setFirstname('Bartholomew Maximilian');
        $contact->setLastname('Fotheringay Wolfeschlegelstein');

        $contactModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $contactModel);
        $contactModel->saveEntities([$contact]);

        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $this->em->persist($segment);

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setCustomHtml('<html>content</html>');
        $email->setEmailType('list');
        $email->addList($segment);
        $this->em->persist($email);

        $reference = new ListLead();
        $reference->setLead($contact);
        $reference->setList($segment);
        $reference->setDateAdded(new \DateTime());
        $this->em->persist($reference);
        $this->em->flush();

        $emailRepository = self::getContainer()->get(EmailRepository::class);
        $this->assertInstanceOf(EmailRepository::class, $emailRepository);

        $this->assertSame(
            1,
            (int) $emailRepository->getEmailPendingLeads($email->getId(), null, null, true),
            'The contact should start out pending.'
        );

        $transport = new AddressLengthEnforcingTransport(self::ADDRESS_LENGTH_LIMIT);
        $sender    = $this->createSender($transport);

        $sender->setEmail($email, ['email', $email->getId()]);
        $sender->setContact([
            'id'        => $contact->getId(),
            'email'     => $contact->getEmail(),
            'firstname' => $contact->getFirstname(),
            'lastname'  => $contact->getLastname(),
            'owner_id'  => 0,
        ]);
        $sender->send();
        $sender->finalFlush();

        $this->assertSame(
            1,
            $transport->getSendCount(),
            'The send must reach the transport, otherwise this test proves nothing.'
        );

        // Metadata is only attached on a tokenized send, so this pins the test to the batch path.
        $this->assertNotEmpty(
            $transport->getLastMetadata(),
            'The send must take the tokenized path, or it is not exercising the defect.'
        );

        $this->em->clear();

        // The reported symptom: pending again after the send, so every run retries the contact.
        $this->assertSame(
            0,
            (int) $emailRepository->getEmailPendingLeads($email->getId(), null, null, true),
            'The contact must not still be pending after the send, or every run will retry it forever.'
        );

        $enforced = $transport->getLastEnforcedRecipients();
        $this->assertCount(1, $enforced);
        $this->assertSame(
            '',
            $enforced[0]->getName(),
            'The display name must have been dropped before the transport built the recipient.'
        );
    }

    /**
     * MailHelper is built here rather than fetched from the container because replacing
     * the mailer service after boot does not reach it: the swap leaves doSend() at zero
     * calls and the test passes without the fix, which proves nothing.
     */
    private function createSender(AddressLengthEnforcingTransport $transport): SendEmailToContact
    {
        $container = self::getContainer();

        $mailHelper = new MailHelper(
            new Mailer($transport),
            $container->get(FromEmailHelper::class),
            $container->get(CoreParametersHelper::class),
            $container->get(Mailbox::class),
            new NullLogger(),
            $container->get(MailHashHelper::class),
            $container->get(RouterInterface::class),
            $container->get(Environment::class),
            $container->get(ThemeHelper::class),
            $container->get(PathsHelper::class),
            $container->get(EventDispatcherInterface::class),
            $container->get(RequestStack::class),
            $container->get(EntityManagerInterface::class),
            $container->get(AssetModel::class),
            $container->get(TrackableModel::class),
            $container->get(RedirectModel::class),
            $container->get(SMimeHelper::class),
            $container->get(EmailStatModel::class),
            $container->get(CopyRepository::class),
        );

        return new SendEmailToContact(
            $mailHelper,
            $container->get(StatHelper::class),
            $container->get(DoNotContact::class),
            $container->get(TranslatorInterface::class),
        );
    }
}
