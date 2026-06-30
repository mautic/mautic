<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\BuilderSubscriber;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderSubscriberTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelper;

    private BuilderSubscriber $builderSubscriber;

    private MockObject&EmailModel $emailModel;

    private MockObject&TranslatorInterface $translator;

    private MockObject&LeadRepository $leadRepository;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->emailModel           = $this->createMock(EmailModel::class);
        $trackableModel             = $this->createMock(TrackableModel::class);
        $redirectModel              = $this->createMock(RedirectModel::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->leadRepository       = $this->createMock(LeadRepository::class);
        $fromEmailHelper            = new FromEmailHelper($this->coreParametersHelper, $this->leadRepository);
        $this->builderSubscriber    = new BuilderSubscriber(
            $this->coreParametersHelper,
            $this->emailModel,
            $trackableModel,
            $redirectModel,
            $this->translator,
            new MailHashHelper($this->coreParametersHelper),
            $fromEmailHelper
        );

        parent::setUp();
    }

    public function testOwnerSignatureIsUsedOnEmailGenerate(): void
    {
        $email = new Email();
        $email->setUseOwnerAsMailer(true);

        $event = new EmailSendEvent(null, [
            'email' => $email,
            'lead'  => [
                'owner_id' => 1,
                'email'    => 'contact1@somewhere.com',
            ],
        ]);

        $this->leadRepository->expects($this->once())
            ->method('getLeadOwner')
            ->with(1)
            ->willReturn([
                'email'      => 'owner1@example.com',
                'first_name' => 'Owner',
                'last_name'  => 'One',
                'signature'  => 'Owner Signature',
            ]);

        $this->coreParametersHelper->method('get')->willReturnMap([
            ['unsubscribe_text', null, null],
            ['webview_text', null, null],
            ['default_signature_text', null, 'Default Signature'],
            ['brand_name', null, 'Brand Name'],
            ['mailer_from_email', null, 'nobody@nowhere.com'],
            ['mailer_from_name', null, 'No Body'],
            ['secret_key', null, 'secret'],
        ]);

        $this->builderSubscriber->onEmailGenerate($event);

        $this->assertSame('Owner Signature', $event->getTokens()['{signature}']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fixEmailAccessibilityContent')]
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
    public static function fixEmailAccessibilityContent(): iterable
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
        $matcher                  = $this->exactly(5);

        $this->coreParametersHelper->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher, $unsubscribeTokenizedText) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('unsubscribe_text', $parameters[0]);

                    return $unsubscribeTokenizedText;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('webview_text', $parameters[0]);

                    return 'Just a text';
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('default_signature_text', $parameters[0]);

                    return 'Signature';
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mailer_from_name', $parameters[0]);

                    return 'jan.kozak@acquia.com';
                }
                if (5 === $matcher->numberOfInvocations()) {
                    $this->assertSame('brand_name', $parameters[0]);

                    return 'ACME';
                }
            });
        $this->emailModel->method('buildUrl')->willReturn('https://some.url');
        $this->translator->method('trans')->willReturn('some translation');

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

        $callCount         = 0;
        $expectedKeys      = ['secret_key', 'unsubscribe_text', 'webview_text', 'default_signature_text', 'mailer_from_name', 'brand_name'];
        $expectedResponses = [
            'secret',
            $unsubscribeTokenizedText,
            'Just a text',
            'Signature',
            'jan.kozak@acquia.com',
            'ACME',
        ];
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(function ($key) use (&$callCount, $expectedKeys, $expectedResponses) {
                if ($callCount < count($expectedKeys)) {
                    $this->assertSame($expectedKeys[$callCount], $key);
                }

                return $expectedResponses[$callCount++] ?? null;
            });

        $emailHash = hash_hmac('sha256', 'lukas.sykora@acquia.com', 'secret');
        $this->emailModel->method('buildUrl')
            ->willReturnCallback(fn ($route) => match ($route) {
                'mautic_email_unsubscribe' => '/email/unsubscribe/hash/lukas.sykora@acquia.com/'.$emailHash,
                'mautic_email_webview'     => '/email/webview/'.$emailHash,
                'mautic_email_preview'     => '/email/preview/111',
                default                    => null,
            });

        $this->translator->method('trans')
            ->willReturn($unsubscribeTokenizedText);

        $this->builderSubscriber->onEmailGenerate($event);
        $this->assertEquals(
            '<a href="/email/unsubscribe/hash/lukas.sykora@acquia.com/'.$emailHash.'">Unsubscribe</a> '.$company->getName().' '.$lead->getLastname(),
            $event->getTokens()['{unsubscribe_text}']
        );
    }
}
