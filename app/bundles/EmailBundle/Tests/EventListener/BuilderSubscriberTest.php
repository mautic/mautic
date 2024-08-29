<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\BuilderSubscriber;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderSubscriberTest extends TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    private BuilderSubscriber $builderSubscriber;

    /**
     * @var MockObject&EmailModel
     */
    private MockObject $emailModel;

    /**
     * @var MockObject&TrackableModel
     */
    private MockObject $trackableModel;

    /**
     * @var MockObject&RedirectModel
     */
    private MockObject $redirectModel;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->emailModel           = $this->createMock(EmailModel::class);
        $this->trackableModel       = $this->createMock(TrackableModel::class);
        $this->redirectModel        = $this->createMock(RedirectModel::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->builderSubscriber    = new BuilderSubscriber(
            $this->coreParametersHelper,
            $this->emailModel,
            $this->trackableModel,
            $this->redirectModel,
            $this->translator,
            new MailHashHelper($this->coreParametersHelper)
        );

        parent::setUp();
    }

    /**
     * @dataProvider fixEmailAccessibilityContent
     */
    public function testFixEmailAccessibility(string $content, string $expectedContent, ?string $emailLocale): void
    {
        $this->emailModel->method('buildUrl')->willReturn('https://some.url');
        $this->translator->method('trans')->willReturn('some translation');
        $this->coreParametersHelper->method('get')->willReturnCallback(function ($key) {
            if ('locale' === $key) {
                return 'default_locale';
            }

            return false;
        });

        $email = new Email();
        $email->setSubject('A unicorn spotted in Alaska');
        $email->setLanguage($emailLocale);

        $emailSendEvent = new EmailSendEvent(null, ['email' => $email]);
        $emailSendEvent->setContent($content);
        $this->builderSubscriber->fixEmailAccessibility($emailSendEvent);
        $this->builderSubscriber->onEmailGenerate($emailSendEvent);
        $this->assertSame($expectedContent, $emailSendEvent->getContent());
    }

    /**
     * @return iterable<array<int,string>>
     */
    public function fixEmailAccessibilityContent(): iterable
    {
        yield [
            '<html><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html><head></head></html>',
            '<html lang="es"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'es',
        ];
        yield [
            '<html><head></head></html>',
            '<html lang="default_locale"><head><title>A unicorn spotted in Alaska</title></head></html>',
            '',
        ];
        yield [
            "<html>\n\n<head>\n</head>\n</html>",
            "<html lang=\"en\">\n\n<head>\n<title>A unicorn spotted in Alaska</title></head>\n</html>",
            'en',
        ];
        yield [
            '<html lang="en"><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html lang="en"><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'es',
        ];
        yield [
            '<html lang="cs_CZ"><head></head></html>',
            '<html lang="cs_CZ"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html lang="en"><head><title>Existed Title</title></head></html>',
            '<html lang="en"><head><title>Existed Title</title></head></html>',
            'en',
        ];
        yield [
            '<head><title>Existed Title</title></head>',
            '<head><title>Existed Title</title></head>',
            'en',
        ];
        yield [
            '<html><body>xxx</body></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head><body>xxx</body></html>',
            'en',
        ];
    }

    public function testUnsubscribeTestTokensAreReplacedOnEmailGenerate(): void
    {
        $lead = new Lead();
        $lead->setId(7);
        $lead->setLastname('Boss');

        $company = new Company();
        $company->setName('ACME');

        $leadArray                = $lead->convertToArray();
        $leadArray['companies'][] = ['companyname' => $company->getName(), 'is_primary' => true];
        $email                    = new Email();
        $email->setSendToDnc(false);
        $args = [
            'lead'  => $leadArray,
            'email' => $email,
        ];
        $event = new EmailSendEvent(null, $args);

        $unsubscribeTokenizedText = '{contactfield=companyname} {contactfield=lastname}';

        $this->emailModel->method('buildUrl')->willReturn('https://some.url');
        $this->translator->method('trans')->willReturn('some translation');

        $this->coreParametersHelper->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(['unsubscribe_text'], ['webview_text'], ['default_signature_text'], ['mailer_from_name'])
            ->willReturnOnConsecutiveCalls($unsubscribeTokenizedText, 'Just a text', 'Signature', 'jan.kozak@acquia.com');

        $this->translator->expects($this->never())
            ->method('trans')
            ->withConsecutive([$unsubscribeTokenizedText], [])
            ->willReturn($unsubscribeTokenizedText);

        $this->builderSubscriber->onEmailGenerate($event);
        $this->assertEquals(
            $company->getName().' '.$lead->getLastname(),
            $event->getTokens()['{unsubscribe_text}']
        );
    }

    public function testUnsubscribeTestTokensAreReplacedWithHashOnEmailGenerate(): void
    {
        $lead = new Lead();
        $lead->setId(7);
        $lead->setLastname('Sykora');
        $lead->setEmail('lukas.sykora@acquia.com');

        $company = new Company();
        $company->setName('Acquia');

        $leadArray                = $lead->convertToArray();
        $leadArray['companies'][] = ['companyname' => $company->getName(), 'is_primary' => true];

        $email = new Email();
        $email->setSendToDnc(true);
        $email->setId(111);

        $args = [
            'lead'   => $leadArray,
            'email'  => $email,
            'idHash' => 'hash',
        ];
        $event = new EmailSendEvent(null, $args);

        $unsubscribeTokenizedText = '<a href="|URL|">Unsubscribe</a> {contactfield=companyname} {contactfield=lastname}';

        $this->coreParametersHelper->method('get')
            ->withConsecutive(['secret_key'], ['unsubscribe_text'], ['webview_text'], ['default_signature_text'], ['mailer_from_name'])
            ->willReturnOnConsecutiveCalls('secret', $unsubscribeTokenizedText, 'Just a text', 'Signature', 'jan.kozak@acquia.com');

        $emailHash = hash_hmac('sha256', 'lukas.sykora@acquia.com', 'secret');
        $this->emailModel->method('buildUrl')
            ->withConsecutive(
                [
                    'mautic_email_unsubscribe',
                    ['idHash' => 'hash', 'urlEmail' => 'lukas.sykora@acquia.com', 'secretHash' => $emailHash],
                ],
                [
                    'mautic_email_webview',
                    ['idHash' => 'hash'],
                ],
                [
                    'mautic_email_preview',
                    ['objectId' => 111],
                ]
            )->willReturnOnConsecutiveCalls(
                '/email/unsubscribe/hash/lukas.sykora@acquia.com/'.$emailHash,
                '/email/webview/'.$emailHash,
                '/email/preview/111'
            );

        $this->translator->expects($this->never())
            ->method('trans')
            ->withConsecutive([$unsubscribeTokenizedText], [])
            ->willReturn($unsubscribeTokenizedText);

        $this->builderSubscriber->onEmailGenerate($event);
        $this->assertEquals(
            '<a href="/email/unsubscribe/hash/lukas.sykora@acquia.com/'.$emailHash.'">Unsubscribe</a> '.$company->getName().' '.$lead->getLastname(),
            $event->getTokens()['{unsubscribe_text}']
        );
    }
}
