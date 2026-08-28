<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    private ?int $leadId = null;

    /**
     * Tests that use the classic unsubscribe page. Not preference center.
     */
    private const UNSUBSCRIBE_TESTS = [
        'testUnsubscribeWithEmailStat',
        'testUnsubscribeEmail',
        'testHeadRequestWithNoShowContactPreferences',
        'testUnsubscribeWithExistingStatRejectsDifferentValidEmailHashPair',
        'testUnsubscribeWithDeletedStatAllowsValidEmailHashPair',
    ];

    protected function setUp(): void
    {
        $this->configParams['show_contact_segments']           = 0;
        $this->configParams['show_contact_frequency']          = 0;
        $this->configParams['show_contact_pause_dates']        = 0;
        $this->configParams['show_contact_categories']         = 0;
        $this->configParams['show_contact_preferred_channels'] = 0;

        $this->configParams['show_contact_preferences'] = 1;
        if (in_array($this->name(), self::UNSUBSCRIBE_TESTS)) {
            $this->configParams['show_contact_preferences'] = 0;
        }

        if (in_array($this->name(), ['testContactPreferencesSaveMessage', 'testLandingPageContactPreferencesSaveMessage'])) {
            $this->configParams['show_contact_segments']           = 1;
            $this->configParams['show_contact_frequency']          = 1;
            $this->configParams['show_contact_pause_dates']        = 1;
            $this->configParams['show_contact_categories']         = 1;
            $this->configParams['show_contact_preferred_channels'] = 1;
        }

        if ('testContactPreferencesFormRenderOnUnsubscribePage' === $this->name()) {
            $this->configParams['show_contact_segments'] = 1;
        }

        switch ($this->getName()) {
            case 'testResubscribeSuccessMessageContainsDirectUnsubscribeLinkWhenValidationDisabled':
            case 'testUnsubscribeSuccessMessageContainsDirectResubscribeLinkWhenValidationDisabled':
                $this->configParams['validate_unsubscribe_emails'] = false;
                break;
        }

        parent::setUp();
    }

    public function testMailerCallbackWhenNoTransportProccessesIt(): void
    {
        $this->client->request('POST', '/mailer/callback');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('No email transport that could process this callback was found', $this->client->getResponse()->getContent());
    }

    public function testMailerCallbackWhenTransportDoesNotProccessIt(): void
    {
        self::getContainer()->get(EventDispatcherInterface::class)->addListener(EmailEvents::ON_TRANSPORT_WEBHOOK, fn (): null => null /* exists but does nothing */);
        $this->client->request('POST', '/mailer/callback');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('No email transport that could process this callback was found', $this->client->getResponse()->getContent());
    }

    public function testMailerCallbackWhenTransportProccessesIt(): void
    {
        self::getContainer()->get(EventDispatcherInterface::class)->addListener(EmailEvents::ON_TRANSPORT_WEBHOOK, fn (TransportWebhookEvent $event) => $event->setResponse(new Response('OK')));
        $this->client->request('POST', '/mailer/callback');

        self::assertResponseIsSuccessful();
        $this->assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testUnsubscribeFormActionWithoutTheme(): void
    {
        $form = $this->getForm(null);
        $stat = $this->getStat($form);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), (string) $crawler->filter('form')->eq(0)->attr('action'));
    }

    public function testContactPreferencesLandingPageTracking(): void
    {
        $this->logoutUser();
        $lead                 = $this->createLead();
        $preferenceCenterPage = $this->getPreferencesCenterLandingPage();
        $stat                 = $this->getStat(null, $lead, $preferenceCenterPage);

        $this->em->flush();

        $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        $this->em->clear();

        $entity = self::getContainer()->get(PageRepository::class)->getEntity($stat->getEmail()->getPreferenceCenter()->getId());
        $this->assertSame(1, $entity->getHits(), $this->client->getResponse()->getContent());
    }

    public function testContactPreferencesSaveMessage(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form();

        // Unsubscribe from email.
        $form->setValues(['lead_contact_frequency_rules[lead_channels][subscribed_channels][0]' => false]);

        $this->assertStringContainsString('/email/unsubscribe/tracking_hash_unsubscribe_form_email', $form->getUri());
        $crawler = $this->client->submit($form);

        self::assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('#success-message-text'), $this->client->getResponse()->getContent());
        $expectedMessage = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.email.preferences_center_success_message.text');
        $this->assertEquals($expectedMessage, trim($crawler->filter('#success-message-text')->text(null, false)));
        $this->assertResponseIsSuccessful();

        // Assert that the contact has the DNC record now.
        $dncRepository = $this->em->getRepository(DoNotContact::class);
        $this->assertInstanceOf(DoNotContactRepository::class, $dncRepository);
        $dncRecords = $dncRepository->findBy(['lead' => $lead->getId()]);
        $this->assertCount(1, $dncRecords);
        $this->assertSame(DoNotContact::UNSUBSCRIBED, $dncRecords[0]->getReason());
        $this->assertSame('email', $dncRecords[0]->getChannel());
        $this->assertSame($stat->getEmail()->getId(), $dncRecords[0]->getChannelId());
    }

    public function testUnsubscribeFormActionWithThemeWithoutFormSupport(): void
    {
        $form = $this->getForm('aurora');
        $stat = $this->getStat($form);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        $this->assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), (string) $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertResponseIsSuccessful();
    }

    public function testUnsubscribeFormActionWithThemeWithFormSupport(): void
    {
        $form = $this->getForm('blank');
        $stat = $this->getStat($form);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        $this->assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), (string) $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertResponseIsSuccessful();
    }

    public function testWithoutUnsubscribeFormAction(): void
    {
        $this->getForm('blank');

        $stat = $this->getStat();

        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        $this->assertStringNotContainsString('form/submit?formId=', $crawler->html());
        $this->assertResponseIsSuccessful();
    }

    public function testOneClickUnsubscribeAction(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();
        $this->client->request('POST', $this->buildUnsubscribeUrl($stat), [
            'List-Unsubscribe' => 'One-Click',
        ]);
        $this->assertResponseIsSuccessful();
        $dncCollection = $stat->getLead()->getDoNotContact();
        $this->assertCount(1, $dncCollection);
        $this->assertEquals(DoNotContact::UNSUBSCRIBED, $dncCollection->first()->getReason());
    }

    public function testOneClickUnsubscribeWithWrongSecretHashIsForbidden(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();
        $this->client->request('POST', "/email/unsubscribe/{$stat->getTrackingHash()}/{$stat->getEmailAddress()}/wronghash", [
            'List-Unsubscribe' => 'One-Click',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $dncCollection = $stat->getLead()->getDoNotContact();
        $this->assertCount(0, $dncCollection);
    }

    public function testHeadRequestWithNoShowContactPreferences(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();
        $this->client->request('HEAD', $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();
        $dncCollection = $stat->getLead()->getDoNotContact();
        $this->assertCount(0, $dncCollection);
    }

    public function testUnsubscribeActionWithCustomPreferenceCenterHasCsrfToken(): void
    {
        $this->logoutUser();
        $lead              = $this->createLead();
        $preferencesCenter = $this->createCustomPreferencesPage('{segmentlist}{saveprefsbutton}');
        $stat              = $this->getStat(null, $lead, $preferencesCenter);
        $this->em->flush();
        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();
        $tokenInput = $crawler->filter('input[name="lead_contact_frequency_rules[_token]"]');
        $this->assertCount(1, $tokenInput, $this->client->getResponse()->getContent());
    }

    private function getPreferencesCenterLandingPage(): Page
    {
        $page = new Page();
        $page->setTitle('Preference center');
        $page->setAlias('Preference-center');
        $page->setIsPublished(true);
        $page->setIsPreferenceCenter(true);
        $page->setCustomHtml('<html><body>{saveprefsbutton}</body></html>');
        $this->em->persist($page);

        return $page;
    }

    public function testUnsubscribeFormActionWithUsingLandingPageWithoutContactLocale(): void
    {
        $lead = $this->createLead();
        $page = $this->createPage();

        $stat = $this->getStat(null, $lead, $page);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Save preferences', $crawler->html());
    }

    /**
     * @return iterable<string, array{contactLocale: string|null, pageLocale: string|null, expectedLocale: string}>
     */
    public static function dataForTestUnsubscribeFormActionWithUsingLandingPage(): iterable
    {
        yield 'No page or contact locale, default to "en"' => [
            'contactLocale'  => null,
            'pageLocale'     => null,
            'expectedLocale' => 'en',
        ];

        yield 'Page locale is set, default to page locale' => [
            'contactLocale'  => null,
            'pageLocale'     => 'de',
            'expectedLocale' => 'de',
        ];

        yield 'Contact locale is set, default to contact locale' => [
            'contactLocale'  => 'de',
            'pageLocale'     => null,
            'expectedLocale' => 'de',
        ];

        yield 'Contact locale overrides page locale' => [
            'contactLocale'  => 'fr',
            'pageLocale'     => 'de',
            'expectedLocale' => 'fr',
        ];

        yield 'Both locales same, use shared locale' => [
            'contactLocale'  => 'fr',
            'pageLocale'     => 'fr',
            'expectedLocale' => 'fr',
        ];

        yield 'Invalid page locale, fallback to contact locale' => [
            'contactLocale'  => 'de',
            'pageLocale'     => 'xx', // Assume 'xx' is not a valid locale
            'expectedLocale' => 'de',
        ];

        yield 'Invalid contact locale, fallback to page locale' => [
            'contactLocale'  => 'yy', // Assume 'yy' is not a valid locale
            'pageLocale'     => 'fr',
            'expectedLocale' => 'fr',
        ];

        yield 'Both locales invalid, fallback to default "en"' => [
            'contactLocale'  => 'zz', // Assume 'zz' is not a valid locale
            'pageLocale'     => 'xx', // Assume 'xx' is not a valid locale
            'expectedLocale' => 'en',
        ];
    }

    #[DataProvider('dataForTestUnsubscribeFormActionWithUsingLandingPage')]
    public function testUnsubscribeFormActionWithUsingLandingPage(?string $contactLocale, ?string $pageLocale, string $expectedLocale): void
    {
        $lead = $this->createLead($contactLocale);
        $page = $this->createPage($pageLocale);

        $stat = $this->getStat(null, $lead, $page);
        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));
        $this->assertResponseIsSuccessful();

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $needle     = $translator->trans('mautic.page.form.saveprefs', [], null, $expectedLocale);

        $this->assertStringContainsString($needle, $crawler->html());
    }

    /**
     * @throws ORMException
     */
    protected function getStat(?Form $form = null, ?Lead $lead = null, ?Page $preferenceCenter = null): Stat
    {
        $trackingHash = 'tracking_hash_unsubscribe_form_email';
        $emailName    = 'Test unsubscribe form email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->setUnsubscribeForm($form);
        $email->setPreferenceCenter($preferenceCenter);
        $this->em->persist($email);

        // Create a test email stat.
        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress('john@doe.email');
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $this->em->persist($stat);

        return $stat;
    }

    /**
     * @throws ORMException
     */
    protected function getForm(?string $formTemplate): Form
    {
        $formName = 'unsubscribe_test_form';

        $form = new Form();
        $form->setName($formName);
        $form->setAlias($formName);
        $form->setTemplate($formTemplate);
        $this->em->persist($form);

        return $form;
    }

    protected function createLead(?string $locale = null): Lead
    {
        $lead = new Lead();
        $lead->setEmail('john@doe.email');
        $lead->addUpdatedField('preferred_locale', $locale);
        $this->em->persist($lead);

        return $lead;
    }

    protected function createCustomPreferencesPage(string $html = ''): Page
    {
        $page = new Page();
        $page->setTitle('Contact Preferences');
        $page->setAlias('contact-preferences');
        $page->setTemplate('blank');
        $page->setIsPreferenceCenter(true);
        $page->setIsPublished(true);
        $page->setCustomHtml($html);
        $this->em->persist($page);

        return $page;
    }

    protected function createPage(?string $locale = ''): Page
    {
        $page = new Page();
        $page->setTitle('Page:Page:LandingPagePrefCenter');
        $page->setAlias('page-page-landingPagePrefCenter');
        $page->setIsPublished(true);
        $page->setTemplate('blank');
        $page->setCustomHtml('<h1>Preference center page</h1><br>{saveprefsbutton}');
        $page->setIsPreferenceCenter(true);

        if ($locale) {
            $page->setLanguage($locale);
        }

        $this->em->persist($page);

        return $page;
    }

    public function testPreviewDisabledByDefault(): void
    {
        $emailName    = 'Test preview email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
        $email->setCustomHtml('some content');
        $this->em->persist($email);

        $this->client->request('GET', '/email/preview/'.$email->getId());
        $this->assertTrue($this->client->getResponse()->isNotFound(), $this->client->getResponse()->getContent());

        $email->setPublicPreview(true);
        $this->em->persist($email);

        $this->em->flush();

        $this->client->request('GET', '/email/preview/'.$email->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testPreviewForExpiredEmailForAnonymousUser(): void
    {
        $this->logoutUser();
        $emailName = 'Test preview email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setPublishUp(new \DateTime('-2 day'));
        $email->setPublishDown(new \DateTime('-1 day'));
        $email->setEmailType('template');
        $email->setCustomHtml('some content');
        $email->setPublicPreview(true);
        $this->em->persist($email);

        $this->em->flush();

        $this->client->request('GET', '/email/preview/'.$email->getId());
        $this->assertResponseIsSuccessful();
    }

    /**
     * @throws ORMException
     */
    public function testUnsubscribeEmail(): void
    {
        foreach ($this->getUnsubscribeProvider() as $parameters) {
            $this->runTestUnsubscribeAction(...$parameters);
        }
    }

    /**
     * @throws ORMException
     */
    public function runTestUnsubscribeAction(
        string $statHash,
        string $email,
        string $emailHash,
        string $message,
        bool $addedRow,
    ): void {
        $uri = '/email/unsubscribe/'.$statHash.'/'.$email.'/'.$emailHash;
        $this->client->request(Request::METHOD_GET, $uri);
        $this->assertResponseIsSuccessful();
        $clientResponse = $this->client->getResponse();
        $this->assertStringContainsString($message, (string) $clientResponse->getContent());

        if ($addedRow) {
            $this->assertStringContainsString(
                '/email/validate/resubscribe/'.$this->getSecretHash($email).'/'.$statHash,
                (string) $clientResponse->getContent()
            );
        }

        $doNotContacts       = $this->em->getRepository(DoNotContact::class)->findBy(['lead' => $this->leadId]);
        $isAddedDoNotContact = (bool) count($doNotContacts);
        $addedDoNotContact   = $isAddedDoNotContact ? $doNotContacts[0] : null;
        $this->assertSame($addedRow, $isAddedDoNotContact);
        // Cleaning
        if ($isAddedDoNotContact) {
            $this->em->remove($addedDoNotContact);
            $this->em->flush();
        }
    }

    private function buildUnsubscribeUrl(Stat $stat): string
    {
        return "/email/unsubscribe/{$stat->getTrackingHash()}/{$stat->getEmailAddress()}/{$this->getSecretHash($stat->getEmailAddress())}";
    }

    /**
     * @return array<string,array<string|bool>>
     *
     * @throws ORMException
     *
     * @see self::testUnsubscribeEmail()
     */
    private function getUnsubscribeProvider(): array
    {
        // Emails
        $wrongEmail = 'test@mautictest.sk';
        $rightEmail = 'test@mautictest.cz';
        $lead       = new Lead();
        $lead->setEmail($rightEmail);
        $this->em->persist($lead);
        // Email hash
        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper   = self::getContainer()->get(CoreParametersHelper::class);
        $configSecretEmailHash  = $coreParametersHelper->get('secret_key');
        $rightHashForWrongEmail = hash_hmac('sha256', $wrongEmail, $configSecretEmailHash);
        $rightHashForRightEmail = hash_hmac('sha256', $rightEmail, $configSecretEmailHash);
        $wrongHash              = hash_hmac('sha256', 'wrong', $configSecretEmailHash);
        // Stat hash
        $wrongStatHash = 'wrong';
        $rightStatHash = 'right';
        $stat          = new Stat();
        $stat->setTrackingHash($rightStatHash);
        $stat->setLead($lead);
        $stat->setEmailAddress($rightEmail);
        $stat->setDateSent(new \DateTime());
        $this->em->persist($stat);
        // Flush
        $this->em->flush();
        $this->leadId = $lead->getId();

        return [
            'ok' => [
                $rightStatHash,
                $rightEmail,
                $rightHashForRightEmail,
                'We are sorry to see you go!',
                true,
            ],
            'ok_right_stat_hash_wrong_sectet' => [
                $rightStatHash,
                $wrongEmail,
                $wrongHash,
                'Record not found',
                false,
            ],
            'ok_right_email_and_hash' => [
                $wrongStatHash,
                $rightEmail,
                $rightHashForRightEmail,
                'We are sorry to see you go!',
                true,
            ],
            'ko_right_email_and_wrong_hash' => [
                $wrongStatHash,
                $rightEmail,
                $wrongHash,
                'Record not found',
                false,
            ],
            'ko_wrong_email_and_right_hash' => [
                $wrongStatHash,
                $wrongEmail,
                $rightHashForWrongEmail,
                'Record not found',
                false,
            ],
        ];
    }

    public function testUnsubscribeNotFoundEmailStat(): void
    {
        $this->client->request(Request::METHOD_GET, '/email/unsubscribe/non-existant-hash/test@mautic.org/'.$this->getSecretHash('test@mautic.org'));
        $this->assertStringContainsString('Record not found.', strip_tags((string) $this->client->getResponse()->getContent()));
        self::assertResponseIsSuccessful();
    }

    public function testUnsubscribeWithEmailStat(): void
    {
        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('template');
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contact);
        $emailStat->setEmailAddress($contact->getEmail());
        $emailStat->setDateSent(new \DateTime());
        $emailStat->setTrackingHash('existing-tracking-hash');
        $this->em->persist($email);
        $this->em->persist($contact);
        $this->em->persist($emailStat);
        $this->em->flush();

        $this->client->request(
            Request::METHOD_GET,
            '/email/unsubscribe/existing-tracking-hash/john@doe.email/'.$this->getSecretHash('john@doe.email')
        );

        $this->assertStringContainsString('We are sorry to see you go! john@doe.email will no longer receive emails from us. If this was by mistake, click here to re-subscribe.', strip_tags((string) $this->client->getResponse()->getContent()));
        self::assertResponseIsSuccessful();

        /** @var DoNotContactRepository $dncRepository */
        $dncRepository = $this->em->getRepository(DoNotContact::class);

        /** @var DoNotContact[] $dncRecords */
        $dncRecords = $dncRepository->findAll();

        $this->assertCount(1, $dncRecords);
        $this->assertSame($contact->getId(), $dncRecords[0]->getLead()->getId());
        $this->assertSame('email', $dncRecords[0]->getChannel());
        $this->assertSame((int) $email->getId(), (int) $dncRecords[0]->getChannelId());
        $this->assertSame('User unsubscribed.', $dncRecords[0]->getComments());
    }

    public function testUnsubscribeWithExistingStatRejectsDifferentValidEmailHashPair(): void
    {
        $email = new Email();
        $email->setName('Victim Email');
        $email->setSubject('Victim Subject');
        $email->setEmailType('template');

        $victimLead = new Lead();
        $victimLead->setEmail('victim@mautic.tld');

        $attackerLead = new Lead();
        $attackerLead->setEmail('attacker@mautic.tld');

        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($victimLead);
        $emailStat->setEmailAddress((string) $victimLead->getEmail());
        $emailStat->setDateSent(new \DateTime());
        $emailStat->setTrackingHash('existing-stat-hash-for-mismatch-test');

        $this->em->persist($email);
        $this->em->persist($victimLead);
        $this->em->persist($attackerLead);
        $this->em->persist($emailStat);
        $this->em->flush();

        $this->client->request(
            Request::METHOD_GET,
            '/email/unsubscribe/existing-stat-hash-for-mismatch-test/attacker@mautic.tld/'.$this->getSecretHash('attacker@mautic.tld')
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Record not found.', strip_tags((string) $this->client->getResponse()->getContent()));

        /** @var DoNotContactRepository $dncRepository */
        $dncRepository = $this->em->getRepository(DoNotContact::class);

        $this->assertCount(0, $dncRepository->findBy(['lead' => $victimLead->getId()]));
        $this->assertCount(0, $dncRepository->findBy(['lead' => $attackerLead->getId()]));
    }

    public function testUnsubscribeWithDeletedStatAllowsValidEmailHashPair(): void
    {
        $requestLead = new Lead();
        $requestLead->setEmail('request@mautic.tld');
        $this->em->persist($requestLead);
        $this->em->flush();

        // Synthetic stale tracking hash: this simulates a link whose stat once existed but is now deleted.
        $staleTrackingHash = 'deleted-stat-hash-for-unsubscribe-test';

        $this->client->request(
            Request::METHOD_GET,
            '/email/unsubscribe/'.$staleTrackingHash.'/request@mautic.tld/'.$this->getSecretHash('request@mautic.tld')
        );

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('We are sorry to see you go!', strip_tags($content));
        $this->assertStringContainsString('/email/validate/resubscribe/'.$this->getSecretHash('request@mautic.tld').'/'.$staleTrackingHash, $content);

        /** @var DoNotContactRepository $dncRepository */
        $dncRepository = $this->em->getRepository(DoNotContact::class);
        /** @var DoNotContact[] $requestLeadDncRecords */
        $requestLeadDncRecords = $dncRepository->findBy(['lead' => $requestLead->getId()]);

        $this->assertCount(1, $requestLeadDncRecords);
        $this->assertSame(DoNotContact::UNSUBSCRIBED, $requestLeadDncRecords[0]->getReason());
        $this->assertSame('email', $requestLeadDncRecords[0]->getChannel());
    }

    public function testValidateEmailFormRejectsInvalidAction(): void
    {
        $this->client->request(Request::METHOD_GET, '/email/validate/invalid-action/secret/hash');

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testValidateEmailFormWithMissingStatDoesNotThrowError(): void
    {
        $email      = 'validate.without.stat@mautic.tld';
        $secretHash = $this->getSecretHash($email);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/email/validate/unsubscribe/'.$secretHash.'/non-existing-tracking-hash'
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Confirm your email address', strip_tags($crawler->html()));
    }

    public function testValidateEmailFormRedirectsToUnsubscribeWithValidEmail(): void
    {
        $stat       = $this->getStat();
        $email      = $stat->getEmailAddress();
        $secretHash = $this->getSecretHash($email);
        $this->em->flush();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/email/validate/unsubscribe/'.$secretHash.'/'.$stat->getTrackingHash()
        );

        $this->assertStringContainsString('Confirm your email address', $crawler->html());

        $form           = $crawler->selectButton('Verify email')->form();
        $emailFieldName = $crawler->filter('input[type="email"]')->attr('name');
        \assert(is_string($emailFieldName));
        $form[$emailFieldName] = (string) $email;
        $this->client->submit($form);

        $this->assertStringContainsString('We are sorry to see you go!', strip_tags((string) $this->client->getResponse()->getContent()));
    }

    public function testValidateEmailFormShowsErrorForMismatchedEmail(): void
    {
        $stat       = $this->getStat();
        $secretHash = $this->getSecretHash($stat->getEmailAddress());
        $this->em->flush();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/email/validate/unsubscribe/'.$secretHash.'/'.$stat->getTrackingHash()
        );

        $form           = $crawler->selectButton('Verify email')->form();
        $emailFieldName = $crawler->filter('input[type="email"]')->attr('name');
        \assert(is_string($emailFieldName));
        $form[$emailFieldName] = 'mismatch@email.tld';
        $crawler               = $this->client->submit($form);

        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('This email address does not match the email address that was used to generate this link.', strip_tags($crawler->html()));
        $this->assertStringNotContainsString('mautic.email.address.does.not.match.link', strip_tags($crawler->html()));
        $this->assertStringContainsString('Confirm your email address', strip_tags($crawler->html()));
        $this->assertStringNotContainsString('We are sorry to see you go!', strip_tags($crawler->html()));
    }

    public function testLegacyResubscribeLinkRedirectsToValidateEmailForm(): void
    {
        $stat = $this->getStat();
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/email/resubscribe/'.$stat->getTrackingHash());

        $this->assertStringContainsString('Confirm your email address', $crawler->html());
    }

    public function testValidateEmailFormRedirectsToResubscribeWithValidEmail(): void
    {
        $stat       = $this->getStat();
        $email      = $stat->getEmailAddress();
        $secretHash = $this->getSecretHash($email);
        $idHash     = $stat->getTrackingHash();
        $this->em->flush();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/email/validate/resubscribe/'.$secretHash.'/'.$idHash
        );

        $form           = $crawler->selectButton('Verify email')->form();
        $emailFieldName = $crawler->filter('input[type="email"]')->attr('name');
        \assert(is_string($emailFieldName));
        $form[$emailFieldName] = (string) $email;
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('has been re-subscribed', strip_tags((string) $this->client->getResponse()->getContent()));
        $this->assertStringContainsString('/email/validate/unsubscribe/'.$secretHash.'/'.$idHash, (string) $this->client->getResponse()->getContent());
    }

    public function testLegacyResubscribeLinkWithoutStatReturnsNotFound(): void
    {
        $this->client->request(Request::METHOD_GET, '/email/resubscribe/non-existing-tracking-hash');

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testResubscribeWithInvalidHashShowsRecordNotFound(): void
    {
        $stat       = $this->getStat();
        $email      = $stat->getEmailAddress();
        $this->em->flush();

        $this->client->request(
            Request::METHOD_GET,
            '/email/resubscribe/'.$stat->getTrackingHash().'/'.$email.'/invalid-hash'
        );

        $this->assertStringContainsString('Record not found.', strip_tags((string) $this->client->getResponse()->getContent()));
    }

    public function testResubscribeWithoutStatButValidHashWorks(): void
    {
        $email      = 'resubscribe.without.stat@mautic.tld';
        $secretHash = $this->getSecretHash($email);
        $idHash     = 'non-existing-tracking-hash';

        $this->client->request(
            Request::METHOD_GET,
            '/email/resubscribe/'.$idHash.'/'.$email.'/'.$secretHash
        );

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('/email/validate/unsubscribe/'.$secretHash.'/'.$idHash, (string) $this->client->getResponse()->getContent());
    }

    public function testWebviewReturns404ForAnonymousUser(): void
    {
        // Create a lead and email stat
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();

        // Get the unsubscribe page
        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        self::assertResponseIsSuccessful();

        // Assert that the link for unsubscribe all exists
        $unsubscribeAllLink = $crawler->filter('a[href^="/email/dnc/"]')->first();
        $this->assertCount(1, $unsubscribeAllLink, 'Unsubscribe all link not found');
        $href = $unsubscribeAllLink->attr('href');

        // Click the link for unsubscribe all
        $this->client->request('GET', $href);

        self::assertResponseIsSuccessful();

        // Assert that the response contains the expected string
        $this->assertStringContainsString(
            'We are sorry to see you go! john@doe.email will no longer receive emails from us',
            (string) $this->client->getResponse()->getContent()
        );

        // Assert that a DoNotContact record was created
        /** @var DoNotContactRepository $dncRepository */
        $dncRepository = $this->em->getRepository(DoNotContact::class);

        /** @var DoNotContact[] $dncRecords */
        $dncRecords = $dncRepository->findBy(['lead' => $lead]);

        $this->assertCount(1, $dncRecords, 'Expected one DoNotContact record');
        $this->assertEquals(DoNotContact::UNSUBSCRIBED, $dncRecords[0]->getReason(), 'Expected reason to be UNSUBSCRIBED');
        $this->assertEquals('email', $dncRecords[0]->getChannel(), 'Expected channel to be email');
    }

    public function testLandingPageContactPreferencesSaveMessage(): void
    {
        $lead = $this->createLead();

        $page = $this->createCustomPreferencesPage('<html lang=""><body>{successmessage}<br/>{saveprefsbutton}</body></html>');

        $stat = $this->getStat(null, $lead, $page);
        $this->em->flush();
        $this->em->clear();

        $this->logoutUser();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form();

        $this->assertStringContainsString('/email/unsubscribe/tracking_hash_unsubscribe_form_email', $form->getUri());

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $successMessage = $crawler->filter('div.pref-successmessage');
        $this->assertCount(1, $successMessage);
    }

    public function testContactPreferencesFormRenderOnUnsubscribePage(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);

        // Preference-center segments - unique public names
        $segmentOne = $this->createSegment('Segment First', 'Segment 2', 'segment-1');
        $segmentTwo = $this->createSegment('Segment Second', 'Segment 1', 'segment-2');

        // Same public name segments (must both render, deterministic order)
        $sameNameOne = $this->createSegment('Same A', 'Same Name', 'same-1');
        $sameNameTwo = $this->createSegment('Same B', 'Same Name', 'same-2');

        // Unpublished preference segment (should NOT appear)
        $unpublishedSegment = $this->createSegment('Draft Segment', 'Draft', 'draft-segment', false);

        // Non-preference segment (should NOT appear)
        $nonPreferenceSegment = $this->createSegment('Hidden Segment', 'Should Not Appear', 'hidden-segment', true, false);

        $this->em->flush();

        $crawler = $this->client->request('GET', $this->buildUnsubscribeUrl($stat));

        $this->assertResponseIsSuccessful();

        // Collect only segment labels
        $labels = $crawler->filter('#contact-segments label[for]')
            ->each(fn ($node): string => trim($node->text()));

        $this->assertSame(
            [
                // same publicName → sorted by ID for stability
                sprintf('%s (%s)', $sameNameOne->getPublicName(), $sameNameOne->getId()),
                sprintf('%s (%s)', $sameNameTwo->getPublicName(), $sameNameTwo->getId()),

                // sorted by publicName
                sprintf('%s (%s)', $segmentTwo->getPublicName(), $segmentTwo->getId()), // Segment 1
                sprintf('%s (%s)', $segmentOne->getPublicName(), $segmentOne->getId()), // Segment 2
            ],
            $labels,
            'Segments must be ordered by publicName, then by ID for stability'
        );

        // Assert: non-preference and unpublished segments are excluded
        $labelText = implode(' ', $labels);

        $this->assertStringNotContainsString($nonPreferenceSegment->getPublicName(), $labelText);
        $this->assertStringNotContainsString($unpublishedSegment->getPublicName(), $labelText);

        // Assert: checkbox ↔ label wiring
        $crawler->filter('#contact-segments input[type="checkbox"]')->each(
            function ($input) use ($crawler): void {
                $id = $input->attr('id');

                $this->assertGreaterThan(
                    0,
                    $crawler->filter(sprintf('label[for="%s"]', $id))->count(),
                    sprintf('Missing label for checkbox %s', $id)
                );
            }
        );
    }

    private function createSegment(
        string $name,
        string $publicName,
        string $alias,
        bool $isPublished = true,
        bool $isPreferenceCenter = true): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($publicName);
        $segment->setAlias($alias);
        $segment->setIsPreferenceCenter($isPreferenceCenter);
        $segment->setIsPublished($isPublished);
        $this->em->persist($segment);

        return $segment;
    }

    public function testResubscribeSuccessMessageContainsDirectUnsubscribeLinkWhenValidationDisabled(): void
    {
        $stat       = $this->getStat();
        $email      = $stat->getEmailAddress();
        $secretHash = $this->getSecretHash($email);
        $idHash     = $stat->getTrackingHash();
        $this->em->flush();

        $this->client->request(
            Request::METHOD_GET,
            '/email/resubscribe/'.$idHash.'/'.$email.'/'.$secretHash
        );

        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertStringContainsString(
            'has been re-subscribed',
            strip_tags((string) $this->client->getResponse()->getContent())
        );
        Assert::assertStringContainsString(
            '/email/unsubscribe/'.$idHash.'/'.$email.'/'.$secretHash,
            (string) $this->client->getResponse()->getContent()
        );
        Assert::assertStringNotContainsString(
            '/email/validate/',
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testUnsubscribeSuccessMessageContainsDirectResubscribeLinkWhenValidationDisabled(): void
    {
        $stat       = $this->getStat();
        $email      = $stat->getEmailAddress();
        $secretHash = $this->getSecretHash($email);
        $idHash     = $stat->getTrackingHash();
        $this->em->flush();

        $this->client->request(
            Request::METHOD_GET,
            '/email/unsubscribe/'.$idHash.'/'.$email.'/'.$secretHash
        );

        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertStringContainsString(
            'will no longer receive emails from us',
            strip_tags((string) $this->client->getResponse()->getContent())
        );
        Assert::assertStringContainsString(
            '/email/resubscribe/'.$idHash.'/'.$email.'/'.$secretHash,
            (string) $this->client->getResponse()->getContent()
        );
        Assert::assertStringNotContainsString(
            '/email/validate/',
            (string) $this->client->getResponse()->getContent()
        );
    }

    private function getSecretHash(string $email): string
    {
        $mailHashHelper = self::getContainer()->get(MailHashHelper::class);

        return $mailHashHelper->getEmailHash($email);
    }
}
