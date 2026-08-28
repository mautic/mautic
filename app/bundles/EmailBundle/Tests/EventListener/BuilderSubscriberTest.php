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
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Entity\TrackableRepository;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BuilderSubscriberTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelper;

    private MockObject&EmailModel $emailModel;

    private MockObject&TranslatorInterface $translator;

    private BuilderSubscriber $builderSubscriber;

    private MockObject&LeadRepository $leadRepository;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->emailModel           = $this->createMock(EmailModel::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->leadRepository       = $this->createMock(LeadRepository::class);
        $fromEmailHelper            = new FromEmailHelper($this->coreParametersHelper, $this->leadRepository);
        $this->builderSubscriber    = new BuilderSubscriber(
            $this->coreParametersHelper,
            $this->emailModel,
            $this->createStub(TrackableModel::class),
            $this->createStub(RedirectModel::class),
            $this->translator,
            new MailHashHelper($this->coreParametersHelper),
            $fromEmailHelper,
            $this->createStub(TrackableRepository::class),
            $this->createStub(RedirectRepository::class)
        );

        parent::setUp();
    }

    public function testOwnerSignatureIsUsedOnEmailGenerate(): void
    {
        $lead = new Lead();
        $lead->setId(7);
        $lead->setLastname('Boss');
        $lead->setEmail('lukas.sykora@acquia.com');

        $company = new Company();
        $company->setName('ACME');

        $email = new Email();
        $email->setUseOwnerAsMailer(true);

        $leadArray                = $lead->convertToArray();
        $leadArray['owner_id']    = 1;
        $leadArray['companies'][] = ['companyname' => $company->getName(), 'is_primary' => true];

        $event = new EmailSendEvent(null, [
            'email'  => $email,
            'lead'   => $leadArray,
            'idHash' => 'hash',
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

        $unsubscribeTokenizedText = '<a href="|URL|">Unsubscribe</a> {contactfield=companyname} {contactfield=lastname}';

        $this->coreParametersHelper->expects($this->exactly(8))->method('get')->willReturnMap([
            ['unsubscribe_text', null, $unsubscribeTokenizedText],
            ['validate_unsubscribe_emails', null, true],
            ['webview_text', null, 'Just a text'],
            ['default_signature_text', null, 'Default Signature'],
            ['brand_name', null, 'Brand Name'],
            ['mailer_from_email', null, 'nobody@nowhere.com'],
            ['mailer_from_name', null, 'No Body'],
            ['secret_key', null, 'secret'],
        ]);
        $emailHash = hash_hmac('sha256', 'lukas.sykora@acquia.com', 'secret');
        $this->emailModel
            ->method('buildUrl')
            ->willReturnCallback(static function (string $route, array $parameters): string {
                if ('mautic_email_validate_email_form' === $route) {
                    return sprintf('/email/validate/%s/%s/%s', $parameters['action'], $parameters['secretHash'], $parameters['idHash']);
                }

                if ('mautic_email_webview' === $route) {
                    return sprintf('/email/view/%s', $parameters['idHash']);
                }

                return sprintf('/email/%s/%s', str_replace('mautic_email_', '', $route), $parameters['idHash']);
            });

        $this->builderSubscriber->onEmailGenerate($event);
        $this->assertEquals(
            '<a href="/email/validate/unsubscribe/'.$emailHash.'/hash">Unsubscribe</a> '.$company->getName().' '.$lead->getLastname(),
            $event->getTokens()['{unsubscribe_text}']
        );
        $this->assertSame('/email/validate/unsubscribe/'.$emailHash.'/hash', $event->getTokens()['{unsubscribe_url}']);
        $this->assertSame('/email/validate/resubscribe/'.$emailHash.'/hash', $event->getTokens()['{resubscribe_url}']);
        $this->assertSame('Owner Signature', $event->getTokens()['{signature}']);
    }

    #[DataProvider('fixEmailAccessibilityContent')]
    public function testFixEmailAccessibility(string $content, string $expectedContent, ?string $emailLocale): void
    {
        $this->emailModel->method('buildUrl')->willReturn('https://some.url');
        $this->translator->method('trans')->willReturn('some translation');
        $this->coreParametersHelper->method('get')->willReturnCallback(function ($key): string|false {
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
            ->willReturnCallback(function ($key) use (&$callCount, $expectedKeys, $expectedResponses): ?string {
                if ($callCount < count($expectedKeys)) {
                    $this->assertSame($expectedKeys[$callCount], $key);
                }

                return $expectedResponses[$callCount++] ?? null;
            });

        $emailHash = hash_hmac('sha256', 'lukas.sykora@acquia.com', 'secret');
        $this->emailModel->method('buildUrl')
            ->willReturnCallback(fn (string $route, array $routeParams = []): string => match (true) {
                'mautic_email_validate_email_form' === $route && ($routeParams['action'] ?? '') === 'unsubscribe' => '/email/validate/unsubscribe/'.$emailHash.'/hash',
                'mautic_email_validate_email_form' === $route                                                    => '/email/validate/resubscribe/'.$emailHash.'/hash',
                'mautic_email_webview' === $route                                                               => '/email/webview/'.$emailHash,
                'mautic_email_preview' === $route                                                               => '/email/preview/111',
                default                                                                                         => '',
            });

        $this->translator->method('trans')
            ->willReturn($unsubscribeTokenizedText);

        $this->builderSubscriber->onEmailGenerate($event);
        $this->assertEquals(
            '<a href="/email/validate/unsubscribe/'.$emailHash.'/hash">Unsubscribe</a> '.$company->getName().' '.$lead->getLastname(),
            $event->getTokens()['{unsubscribe_text}']
        );
    }

    public function testUnsubscribeUrlsAreDirectWhenValidationDisabled(): void
    {
        $lead = new Lead();
        $lead->setId(7);
        $lead->setEmail('test@example.com');

        $leadArray = $lead->convertToArray();

        $email = new Email();
        $email->setSendToDnc(true);

        $args = [
            'lead'   => $leadArray,
            'email'  => $email,
            'idHash' => 'testhash',
        ];
        $event = new EmailSendEvent(null, $args);

        $this->coreParametersHelperMock
            ->method('get')
            ->withConsecutive(['secret_key'], ['unsubscribe_text'], ['validate_unsubscribe_emails'], ['webview_text'], ['default_signature_text'], ['mailer_from_name'])
            ->willReturnOnConsecutiveCalls('secret', null, false, null, null, null);

        $emailHash = hash_hmac('sha256', 'test@example.com', 'secret');
        $this->emailModelMock
            ->method('buildUrl')
            ->willReturnCallback(static function (string $route, array $parameters): string {
                if ('mautic_email_unsubscribe' === $route) {
                    return sprintf('/email/unsubscribe/%s/%s/%s', $parameters['idHash'], $parameters['urlEmail'], $parameters['secretHash']);
                }

                if ('mautic_email_resubscribe' === $route) {
                    return sprintf('/email/resubscribe/%s/%s/%s', $parameters['idHash'], $parameters['urlEmail'], $parameters['secretHash']);
                }

                if ('mautic_email_webview' === $route) {
                    return sprintf('/email/view/%s', $parameters['idHash']);
                }

                return '/';
            });

        $this->translatorMock
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters = []): string {
                if ('mautic.email.unsubscribe.text' === $id) {
                    return str_replace('%link%', $parameters['%link%'], 'Click here to unsubscribe: %link%');
                }

                return $id;
            });

        $this->builderSubscriber->onEmailGenerate($event);

        $this->assertSame('/email/unsubscribe/testhash/test@example.com/'.$emailHash, $event->getTokens()['{unsubscribe_url}']);
        $this->assertSame('/email/resubscribe/testhash/test@example.com/'.$emailHash, $event->getTokens()['{resubscribe_url}']);
    }
}
