<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailForward;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailForwardFunctionalTest extends MauticMysqlTestCase
{
    private const TRACKING_HASH = 'forwardtesthash';

    private const RECIPIENT_EMAIL = 'john@doe.email';

    private const RECIPIENT_FIRSTNAME = 'Johnathan';

    private const FRIEND_EMAIL = 'friend@example.test';

    private const PUBLIC_LINK = 'https://example.test/public-page';

    private const FORWARD_LIMIT = 2;

    private const MAILBOX = ['address' => 'inbox@example.test', 'host' => 'imap.example.test', 'port' => '993', 'encryption' => '/ssl', 'user' => 'inbox', 'password' => 'secret', 'override_settings' => 0, 'folder' => 'INBOX'];

    private const MONITORED_INBOX = [
        'general'                  => self::MAILBOX,
        'EmailBundle_bounces'      => self::MAILBOX,
        'EmailBundle_unsubscribes' => self::MAILBOX,
        'EmailBundle_replies'      => self::MAILBOX,
    ];

    private const CONFIGURED_FORWARD_TEXT = '<a href="|URL|">Share with a colleague</a>';

    private const EMAIL_HTML = '<html><body>'
        .'<p>Hi {contactfield=firstname}</p>'
        .'<a href="'.self::PUBLIC_LINK.'">Read more</a>'
        .'<a href="{unsubscribe_url}">Unsubscribe</a>'
        .'<a href="{dnc_url}">No contact</a>'
        .'<a href="{resubscribe_url}">Resubscribe</a>'
        .'<a href="{webview_url}">Web view</a>'
        .'<a href="{forward_url}">Forward</a>'
        .'{unsubscribe_text}'
        .'<p id="forward-text">{forward_text}</p>'
        .'<p id="owner">{ownerfield=firstname}{ownerfield=email}</p>'
        .'</body></html>';

    protected function setUp(): void
    {
        $this->configParams['mailer_from_name']            = 'Mautic Admin';
        $this->configParams['mailer_from_email']           = 'admin@email.com';
        $this->configParams['disable_trackable_urls']      = false;
        $this->configParams['mailer_append_tracking_pixel'] = true;
        $this->configParams['email_forward_limit']         = self::FORWARD_LIMIT;
        // A monitored inbox adds its own unsubscribe address to the headers of a normal send
        $this->configParams['monitored_email']             = self::MONITORED_INBOX;
        // So does an unsubscribe header set by hand in the configuration
        $this->configParams['mailer_custom_headers']       = ['List-Unsubscribe' => '<mailto:configured-unsubscribe@example.test>'];
        $this->configParams['forward_text']                = str_contains($this->name(), 'ConfiguredForwardText') ? self::CONFIGURED_FORWARD_TEXT : null;

        parent::setUp();
    }

    public function testNormalSendCarriesTheForwardLinkForThatSentEmail(): void
    {
        $lead  = $this->createRecipient();
        $email = $this->createEmail();
        $this->em->flush();

        /** @var MailHelper $mailer */
        $mailer = self::getContainer()->get(MailHelper::class)->getMailer();
        $mailer->setLead(['id' => $lead->getId(), 'email' => self::RECIPIENT_EMAIL, 'firstname' => self::RECIPIENT_FIRSTNAME]);
        $mailer->setIdHash(self::TRACKING_HASH, false);
        $mailer->setEmail($email);
        $mailer->setTo(self::RECIPIENT_EMAIL);
        $this->assertTrue($mailer->send(true));

        $message = self::getMailerMessage();
        $this->assertInstanceOf(MauticMessage::class, $message);
        $html = (string) $message->getHtmlBody();
        $this->assertStringContainsString('/email/forward/'.self::TRACKING_HASH, $html);
        // {forward_text} falls back to the translated text and links to the same page
        $forwardText = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.email.forward.text', ['%link%' => '|URL|']);
        [$beforeLink, $afterLink] = explode('|URL|', $forwardText);
        $this->assertMatchesRegularExpression(
            '#<p id="forward-text">'.preg_quote($beforeLink, '#').'[^\'"]*/email/forward/'.self::TRACKING_HASH.preg_quote($afterLink, '#').'</p>#',
            $html
        );
    }

    public function testNormalSendUsesTheConfiguredForwardText(): void
    {
        $lead  = $this->createRecipient();
        $email = $this->createEmail();
        $this->em->flush();

        /** @var MailHelper $mailer */
        $mailer = self::getContainer()->get(MailHelper::class)->getMailer();
        $mailer->setLead(['id' => $lead->getId(), 'email' => self::RECIPIENT_EMAIL, 'firstname' => self::RECIPIENT_FIRSTNAME]);
        $mailer->setIdHash(self::TRACKING_HASH, false);
        $mailer->setEmail($email);
        $mailer->setTo(self::RECIPIENT_EMAIL);
        $this->assertTrue($mailer->send(true));

        $message = self::getMailerMessage();
        $this->assertInstanceOf(MauticMessage::class, $message);
        $this->assertMatchesRegularExpression('#<p id="forward-text"><a href="[^"]*/email/forward/'.self::TRACKING_HASH.'">Share with a colleague</a></p>#', (string) $message->getHtmlBody());
    }

    public function testForwardPageOffersTheFormForASentEmail(): void
    {
        $this->createSentEmail();

        $crawler = $this->client->request(Request::METHOD_GET, '/email/forward/'.self::TRACKING_HASH);

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('input[name="forward_email[emailAddress]"]'));
        $this->assertCount(0, self::getMailerMessages());
    }

    public function testForwardSendsACopyWithNothingPointingAtTheOriginalRecipient(): void
    {
        $stat = $this->createSentEmail();

        $this->submitForward(self::FRIEND_EMAIL);

        self::assertResponseIsSuccessful();
        $this->assertStringNotContainsString('forward_email[emailAddress]', (string) $this->client->getResponse()->getContent());
        $this->assertCount(1, self::getMailerMessages());

        $message = self::getMailerMessage();
        $this->assertInstanceOf(MauticMessage::class, $message);
        $this->assertCount(1, $message->getTo());
        $this->assertSame(self::FRIEND_EMAIL, $message->getTo()[0]->getAddress());

        $html = $message->getHtmlBody();
        $this->assertStringContainsString(self::PUBLIC_LINK, (string) $html, 'Links stay as they are in the copy.');
        $this->assertStringNotContainsString('/r/', (string) $html, 'The copy must not use tracked redirects.');
        $this->assertStringNotContainsString('/email/unsubscribe/', (string) $html);
        $this->assertStringNotContainsString('/email/dnc/', (string) $html);
        $this->assertStringNotContainsString('/email/resubscribe/', (string) $html);
        $this->assertStringNotContainsString('/email/forward/', (string) $html);
        $this->assertStringNotContainsString('/email/view/', (string) $html, 'The web view must not open the original recipient\'s copy.');
        $this->assertStringNotContainsString('/email/preview/', (string) $html, 'Without a public preview there is no web view to link to.');
        $this->assertStringNotContainsString('.gif', (string) $html, 'The copy must not carry an open tracking pixel.');
        $this->assertStringNotContainsString(self::TRACKING_HASH, (string) $html);
        $this->assertStringNotContainsString(self::RECIPIENT_FIRSTNAME, (string) $html, 'The copy must not be personalised for the original recipient.');
        $this->assertStringNotContainsString(self::RECIPIENT_EMAIL, (string) $html);
        $this->assertStringNotContainsString('{', (string) $html, 'Every token must be replaced.');
        $this->assertStringContainsString('<p id="owner"></p>', (string) $html, 'The copy must not name the original recipient\'s owner, nor show a placeholder for it.');
        $this->assertNotInstanceOf(\Symfony\Component\Mime\Header\HeaderInterface::class, $message->getHeaders()->get('List-Unsubscribe'));
        $this->assertNotInstanceOf(\Symfony\Component\Mime\Header\HeaderInterface::class, $message->getHeaders()->get('List-Unsubscribe-Post'));

        $this->assertSame(1, $this->em->getRepository(Stat::class)->count([]), 'The copy must not create a stat.');
        $statId = $stat->getId();
        $this->em->clear();
        $forwards = $this->em->getRepository(EmailForward::class)->findAll();
        $this->assertCount(1, $forwards);
        $this->assertSame($statId, $forwards[0]->getStat()->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $forwards[0]->getDateSent());
    }

    public function testForwardStopsOnceTheLimitForThatSentEmailIsReached(): void
    {
        $this->createSentEmail();

        for ($i = 1; $i <= self::FORWARD_LIMIT; ++$i) {
            $this->submitForward(sprintf('friend%d@example.test', $i));
            self::assertResponseIsSuccessful();
        }

        $this->assertCount(self::FORWARD_LIMIT, self::getMailerMessages());

        $crawler = $this->client->request(Request::METHOD_GET, '/email/forward/'.self::TRACKING_HASH);

        self::assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('input[name="forward_email[emailAddress]"]'), 'No form once the limit is reached.');
    }

    public function testForwardSendsNothingToAnAddressThatAskedNotToBeEmailedAndDoesNotSaySo(): void
    {
        $stat = $this->createSentEmail();

        $friend = new Lead();
        $friend->setEmail(self::FRIEND_EMAIL);
        $dnc = new DoNotContact();
        $dnc->setChannel('email');
        $dnc->setReason(DoNotContact::UNSUBSCRIBED);
        $dnc->setLead($friend);
        $dnc->setDateAdded(new \DateTime());
        $friend->addDoNotContactEntry($dnc);
        $this->em->persist($friend);
        $this->em->flush();

        $crawler = $this->submitForward(self::FRIEND_EMAIL);

        // The page answers as it does for any other address, so it can't be used to find out who unsubscribed
        self::assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('input[name="forward_email[emailAddress]"]'));
        $this->assertCount(0, $crawler->filter('.validate-alert'));
        $this->assertStringContainsString(self::FRIEND_EMAIL, $crawler->text());
        $this->assertCount(0, self::getMailerMessages());
        $this->assertSame(1, $this->em->getRepository(EmailForward::class)->count(['stat' => $stat]), 'The attempt counts toward the limit.');
    }

    public function testCopyLinksToTheWebViewWhenTheEmailHasAPublicPreview(): void
    {
        $stat = $this->createSentEmail();
        $stat->getEmail()->setPublicPreview(true);
        $this->em->flush();

        $this->submitForward(self::FRIEND_EMAIL);

        $message = self::getMailerMessage();
        $this->assertInstanceOf(MauticMessage::class, $message);
        $html = (string) $message->getHtmlBody();
        $this->assertStringContainsString('/email/preview/'.$stat->getEmail()->getId(), $html);
        $this->assertStringNotContainsString('/email/view/', $html);
    }

    public function testForwardPageUsesTheLanguageOfTheContact(): void
    {
        $stat = $this->createSentEmail();
        // The preferred locale is a contact field, so it is stored on the contact's row
        $this->connection->update(MAUTIC_TABLE_PREFIX.'leads', ['preferred_locale' => 'fr'], ['id' => $stat->getLead()->getId()]);
        $this->em->clear();

        // The translator goes back to the default locale once the request is finished, so it is read while responding
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $locale     = null;
        self::getContainer()->get(EventDispatcherInterface::class)->addListener(
            KernelEvents::RESPONSE,
            static function () use ($translator, &$locale): void {
                $locale = $translator->getLocale();
            }
        );

        $this->client->request(Request::METHOD_GET, '/email/forward/'.self::TRACKING_HASH);

        self::assertResponseIsSuccessful();
        $this->assertSame('fr', $locale);
    }

    public function testForwardSendsNothingWithoutAValidFormToken(): void
    {
        $stat = $this->createSentEmail();

        // Posted without loading the page first, as a request made on somebody's behalf would be
        $crawler = $this->client->request(Request::METHOD_POST, '/email/forward/'.self::TRACKING_HASH, [
            'forward_email' => ['emailAddress' => self::FRIEND_EMAIL, '_token' => 'not-the-token'],
        ]);

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.validate-alert'), 'The page says why nothing was sent.');
        $this->assertCount(0, self::getMailerMessages());
        $this->assertSame(0, $this->em->getRepository(EmailForward::class)->count(['stat' => $stat]));
    }

    public function testForwardThatCouldNotBeSentIsNotCountedAndSaysSo(): void
    {
        $stat = $this->createSentEmail();

        $mailer = $this->createMock(MailHelper::class);
        $mailer->method('getMailer')->willReturnSelf();
        $mailer->method('setTo')->willReturn(true);
        $mailer->method('send')->willReturn(false);
        $mailer->method('getErrors')->willReturn(['The mail server refused the message']);
        self::getContainer()->set(MailHelper::class, $mailer);

        $crawler = $this->submitForward(self::FRIEND_EMAIL);

        self::assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('input[name="forward_email[emailAddress]"]'));
        $this->assertStringContainsString(
            self::getContainer()->get(TranslatorInterface::class)->trans('mautic.email.forward.failed'),
            $crawler->text()
        );
        $this->assertStringNotContainsString(self::FRIEND_EMAIL, $crawler->text());
        $this->assertSame(0, $this->em->getRepository(EmailForward::class)->count(['stat' => $stat]), 'A copy that was not sent does not count.');
    }

    public function testForwardIsNotCountedWhenTheAddressCouldNotBeChecked(): void
    {
        $stat = $this->createSentEmail();

        $friend = new Lead();
        $friend->setEmail(self::FRIEND_EMAIL);
        $this->em->persist($friend);
        $this->em->flush();

        $doNotContact = $this->createMock(DoNotContactModel::class);
        $doNotContact->method('isContactable')->willThrowException(new \RuntimeException('The lookup failed'));
        self::getContainer()->set(DoNotContactModel::class, $doNotContact);

        $this->submitForward(self::FRIEND_EMAIL);

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertCount(0, self::getMailerMessages());
        $this->assertSame(0, $this->em->getRepository(EmailForward::class)->count(['stat' => $stat]), 'Nothing went out, so nothing is counted.');
    }

    public function testForwardRejectsAnInvalidAddress(): void
    {
        $this->createSentEmail();

        $crawler = $this->submitForward('not-an-address');

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.validate-alert'));
        $this->assertCount(0, self::getMailerMessages());
    }

    public function testForwardIsNotAvailableForAnUnknownHash(): void
    {
        $this->client->request(Request::METHOD_GET, '/email/forward/unknownhash');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testForwardIsNotAvailableForAnUnpublishedEmail(): void
    {
        $stat = $this->createSentEmail();
        $stat->getEmail()->setIsPublished(false);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/email/forward/'.self::TRACKING_HASH);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function submitForward(string $address): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/email/forward/'.self::TRACKING_HASH);
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();
        $form['forward_email[emailAddress]'] = $address;

        return $this->client->submit($form);
    }

    private function createSentEmail(): Stat
    {
        $lead  = $this->createRecipient();
        $email = $this->createEmail();

        $stat = new Stat();
        $stat->setTrackingHash(self::TRACKING_HASH);
        $stat->setEmailAddress(self::RECIPIENT_EMAIL);
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    private function createRecipient(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname(self::RECIPIENT_FIRSTNAME);
        $lead->setEmail(self::RECIPIENT_EMAIL);
        // The owner's details must not reach the copy either
        $lead->setOwner($this->em->getRepository(User::class)->findOneBy(['username' => 'admin']));
        $this->em->persist($lead);

        return $lead;
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Forward test email');
        $email->setSubject('Forward test email');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->setCustomHtml(self::EMAIL_HTML);
        $email->setIsPublished(true);
        $this->em->persist($email);

        return $email;
    }
}
