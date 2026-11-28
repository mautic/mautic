<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailForwardFunctionalTest extends MauticMysqlTestCase
{
    private const TRACKING_HASH = 'forwardtesthash';

    private const RECIPIENT_EMAIL = 'john@doe.email';

    private const RECIPIENT_FIRSTNAME = 'Johnathan';

    private const PUBLIC_LINK = 'https://example.test/public-page';

    private const CONFIGURED_FORWARD_TEXT = '<a href="|URL|">Share with a colleague</a>';

    private const EMAIL_HTML = '<html><body>'
        .'<p>Hi {contactfield=firstname}</p>'
        .'<a href="'.self::PUBLIC_LINK.'">Read more</a>'
        .'<a href="{unsubscribe_url}">Unsubscribe</a>'
        .'<a href="{dnc_url}">No contact</a>'
        .'<a href="{resubscribe_url}">Resubscribe</a>'
        .'<a id="web-view" href="{webview_url}">Web view</a>'
        .'<a id="forward" href="{forward_url}">Forward</a>'
        .'{unsubscribe_text}'
        .'<p id="forward-text">{forward_text}</p>'
        .'<p id="owner">{ownerfield=firstname}{ownerfield=email}</p>'
        .'</body></html>';

    protected function setUp(): void
    {
        $this->configParams['mailer_from_name']             = 'Mautic Admin';
        $this->configParams['mailer_from_email']            = 'admin@email.com';
        $this->configParams['disable_trackable_urls']       = false;
        $this->configParams['mailer_append_tracking_pixel'] = true;
        $this->configParams['forward_text']                 = str_contains($this->name(), 'ConfiguredForwardText') ? self::CONFIGURED_FORWARD_TEXT : null;
        $this->configParams['forward_mail_subject']         = str_contains($this->name(), 'ForwardMessageOfItsLanguage') ? ['de' => 'WG: |SUBJECT|', 'pt' => 'Enc: |SUBJECT|'] : [];
        $this->configParams['forward_mail_body']            = str_contains($this->name(), 'ForwardMessageOfItsLanguage') ? ['DE' => 'Schau mal: |URL|', 'pt' => 'Veja este e-mail'] : [];

        parent::setUp();
    }

    public function testSentEmailOpensANewMessageThatLinksToTheCopy(): void
    {
        $email = $this->createEmail('Hello {contactfield=firstname|friend}');
        $this->em->flush();

        $html = $this->sendTo($this->createRecipient(), $email);

        $mailto = html_entity_decode($this->filterHtml($html, '#forward')->attr('href'));
        $this->assertStringStartsWith('mailto:?subject=', $mailto, 'No address is filled in: whoever forwards picks it in their own mail client.');
        parse_str((string) parse_url($mailto, PHP_URL_QUERY), $query);

        $translator = self::getContainer()->get(TranslatorInterface::class);
        // The subject greets nobody by name
        $this->assertSame($translator->trans('mautic.email.forward.mail.subject', ['%subject%' => 'Hello friend']), $query['subject']);
        $this->assertStringContainsString($this->getCopyPath($email), (string) $query['body']);
        $this->assertStringNotContainsString(self::TRACKING_HASH, $mailto, 'The message must not carry the link of the recipient who forwards it.');
        $this->assertStringNotContainsString(self::RECIPIENT_FIRSTNAME, $mailto);
        $this->assertStringNotContainsString(self::RECIPIENT_EMAIL, $mailto);

        // {forward_text} falls back to the translated text and holds the same link
        $this->assertSame($mailto, html_entity_decode($this->filterHtml($html, '#forward-text a')->attr('href')));
        $this->assertSame(strip_tags($translator->trans('mautic.email.forward.text')), $this->filterHtml($html, '#forward-text a')->text());
    }

    public function testSentEmailUsesTheConfiguredForwardText(): void
    {
        $email = $this->createEmail();
        $this->em->flush();

        $link = $this->filterHtml($this->sendTo($this->createRecipient(), $email), '#forward-text a');

        $this->assertSame('Share with a colleague', $link->text());
        $this->assertStringStartsWith('mailto:?subject=', $link->attr('href'));
    }

    /**
     * @return iterable<string, array{string, string|null, string|null}>
     */
    public static function forwardMessageLanguageProvider(): iterable
    {
        yield 'the language of the email, whatever the case of its code' => ['de', 'WG: Hello friend', 'Schau mal: '];
        yield 'the base language for a regional one, with the link added to a text without it' => ['pt_BR', 'Enc: Hello friend', "Veja este e-mail\n"];
        yield 'the translation for a language without a text' => ['fr', null, null];
    }

    #[DataProvider('forwardMessageLanguageProvider')]
    public function testSentEmailUsesTheForwardMessageOfItsLanguage(string $language, ?string $expectedSubject, ?string $expectedBodyStart): void
    {
        $email = $this->createEmail('Hello {contactfield=firstname|friend}');
        $email->setLanguage($language);
        $this->em->flush();

        $mailto = html_entity_decode($this->filterHtml($this->sendTo($this->createRecipient(), $email), '#forward')->attr('href'));
        parse_str((string) parse_url($mailto, PHP_URL_QUERY), $query);

        $body       = (string) $query['body'];
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $this->assertSame($expectedSubject ?? $translator->trans('mautic.email.forward.mail.subject', ['%subject%' => 'Hello friend'], null, $language), $query['subject']);
        $this->assertStringEndsWith($this->getCopyPath($email), $body);
        if (null === $expectedBodyStart) {
            $this->assertStringStartsWith(strstr($translator->trans('mautic.email.forward.mail.body', [], null, $language), '%link%', true), $body);

            return;
        }

        $this->assertStringStartsWith($expectedBodyStart, $body);
        $this->assertStringStartsWith('http', substr($body, strlen($expectedBodyStart)), 'The link follows the text.');
    }

    public function testCopyCarriesNothingPointingAtARecipient(): void
    {
        $stat = $this->createSentEmail();

        $this->client->request(Request::METHOD_GET, $this->getCopyPath($stat->getEmail()));

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString(self::PUBLIC_LINK, $html, 'Links stay as they are in the copy.');
        $this->assertStringNotContainsString('/r/', $html, 'The copy must not use tracked redirects.');
        $this->assertStringNotContainsString('/email/unsubscribe/', $html);
        $this->assertStringNotContainsString('/email/dnc/', $html);
        $this->assertStringNotContainsString('/email/resubscribe/', $html);
        $this->assertStringNotContainsString('/email/view/', $html, 'The web view must not open a recipient\'s own copy.');
        $this->assertStringNotContainsString('.gif', $html, 'The copy must not carry an open tracking pixel.');
        $this->assertStringNotContainsString(self::TRACKING_HASH, $html);
        $this->assertStringNotContainsString(self::RECIPIENT_FIRSTNAME, $html, 'The copy must not be personalised for a recipient.');
        $this->assertStringNotContainsString(self::RECIPIENT_EMAIL, $html);
        $this->assertStringNotContainsString('{', $html, 'Every token must be replaced.');
        $this->assertStringContainsString('<p id="owner"></p>', $html, 'The copy must not name a recipient\'s owner, nor show a placeholder for it.');

        // The web view of the copy is the copy, and it can be passed on again
        $this->assertStringEndsWith($this->getCopyPath($stat->getEmail()), $this->filterHtml($html, '#web-view')->attr('href'));
        $this->assertStringStartsWith('mailto:?subject=', $this->filterHtml($html, '#forward')->attr('href'));
    }

    public function testOpeningTheCopyRecordsNothingAndSetsNoCookie(): void
    {
        $stat = $this->createSentEmail();

        $this->client->request(Request::METHOD_GET, $this->getCopyPath($stat->getEmail()));

        self::assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertSame([], $response->headers->getCookies(), 'Whoever opens the copy must not be recognised later.');
        $this->assertSame('noindex', $response->headers->get('X-Robots-Tag'));

        $this->em->clear();
        $stat = $this->em->getRepository(Stat::class)->findOneBy(['trackingHash' => self::TRACKING_HASH]);
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->isRead(), 'The copy must not count as the recipient opening the email.');
        $this->assertSame(0, (int) $stat->getOpenCount());
        $this->assertSame(1, $this->em->getRepository(Stat::class)->count([]));
        $this->assertSame(1, $this->em->getRepository(Lead::class)->count([]), 'Whoever opens the copy must not become a contact.');
        $this->assertSame(0, $this->em->getRepository(Hit::class)->count([]));
    }

    public function testCopyKeepsTheUtmTagsOfTheEmailOnLinksThatAreNotTracked(): void
    {
        $stat = $this->createSentEmail();
        $stat->getEmail()->setUtmTags(['utmSource' => 'newsletter', 'utmCampaign' => 'spring']);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, $this->getCopyPath($stat->getEmail()));

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString(self::PUBLIC_LINK.'?utm_source=newsletter&utm_campaign=spring', html_entity_decode($html));
        $this->assertStringNotContainsString('/r/', $html, 'The copy must not use tracked redirects.');
    }

    public function testCopyIsTheSameLinkForEveryRecipient(): void
    {
        $email = $this->createEmail();
        $this->em->flush();

        $first = $this->filterHtml($this->sendTo($this->createRecipient(), $email), '#forward')->attr('href');

        $other = new Lead();
        $other->setFirstname('Somebody');
        $other->setEmail('somebody@else.test');
        $this->em->persist($other);
        $this->em->flush();

        $this->assertSame($first, $this->filterHtml($this->sendTo($other, $email, 'anotherhash'), '#forward')->attr('href'));
    }

    public function testCopyIsNotAvailableWithoutTheRightSignature(): void
    {
        $email = $this->createSentEmail()->getEmail();
        $this->assertInstanceOf(Email::class, $email);

        $this->client->request(Request::METHOD_GET, '/email/forward/'.$email->getId().'/'.str_repeat('0', 64));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // The signature of one email opens no other email
        $other = $this->createEmail();
        $this->em->flush();
        $this->client->request(Request::METHOD_GET, '/email/forward/'.$other->getId().'/'.$this->getForwardHash($email));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCopyIsNotAvailableForAnUnknownEmail(): void
    {
        $this->client->request(Request::METHOD_GET, '/email/forward/999999/'.str_repeat('0', 64));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCopyIsNotAvailableForAnUnpublishedEmail(): void
    {
        $email = $this->createSentEmail()->getEmail();
        $this->assertInstanceOf(Email::class, $email);
        $email->setIsPublished(false);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, $this->getCopyPath($email));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function getForwardHash(Email $email): string
    {
        return self::getContainer()->get(MailHashHelper::class)->getForwardHash((int) $email->getId());
    }

    private function getCopyPath(Email $email): string
    {
        return '/email/forward/'.$email->getId().'/'.$this->getForwardHash($email);
    }

    private function filterHtml(string $html, string $selector): \Symfony\Component\DomCrawler\Crawler
    {
        return (new \Symfony\Component\DomCrawler\Crawler($html))->filter($selector);
    }

    private function sendTo(Lead $lead, Email $email, string $trackingHash = self::TRACKING_HASH): string
    {
        /** @var MailHelper $mailer */
        $mailer = self::getContainer()->get(MailHelper::class)->getMailer();
        $mailer->setLead(['id' => $lead->getId(), 'email' => $lead->getEmail(), 'firstname' => $lead->getFirstname()]);
        $mailer->setIdHash($trackingHash, false);
        $mailer->setEmail($email);
        $mailer->setTo($lead->getEmail());
        $this->assertTrue($mailer->send(true));

        $messages = self::getMailerMessages();
        $message  = end($messages);
        $this->assertInstanceOf(MauticMessage::class, $message);

        return (string) $message->getHtmlBody();
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
        $this->em->flush();

        return $lead;
    }

    private function createEmail(string $subject = 'Forward test email'): Email
    {
        $email = new Email();
        $email->setName('Forward test email');
        $email->setSubject($subject);
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->setCustomHtml(self::EMAIL_HTML);
        $email->setIsPublished(true);
        $this->em->persist($email);

        return $email;
    }
}
