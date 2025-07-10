<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var int
     */
    private $leadId;

    /**
     * Tests that use the classic unsubscribe page. Not preference center.
     */
    private const UNSUBSCRIBE_TESTS = [
        'testUnsubscribeWithEmailStat',
        'testUnsubscribeEmail',
    ];

    protected function setUp(): void
    {
        $this->configParams['show_contact_segments']           = 0;
        $this->configParams['show_contact_frequency']          = 0;
        $this->configParams['show_contact_pause_dates']        = 0;
        $this->configParams['show_contact_categories']         = 0;
        $this->configParams['show_contact_preferred_channels'] = 0;

        if (in_array($this->getName(), self::UNSUBSCRIBE_TESTS)) {
            $this->configParams['show_contact_preferences'] = 0;
        } else {
            $this->configParams['show_contact_preferences'] = 1;
        }

        if (in_array($this->getName(), ['testContactPreferencesSaveMessage'])) {
            $this->configParams['show_contact_segments']           = 1;
            $this->configParams['show_contact_frequency']          = 1;
            $this->configParams['show_contact_pause_dates']        = 1;
            $this->configParams['show_contact_categories']         = 1;
            $this->configParams['show_contact_preferred_channels'] = 1;
        }

        parent::setUp();
    }

    public function testMailerCallbackWhenNoTransportProccessesIt(): void
    {
        $this->client->request('POST', '/mailer/callback');

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        Assert::assertSame('No email transport that could process this callback was found', $this->client->getResponse()->getContent());
    }

    public function testMailerCallbackWhenTransportDoesNotProccessIt(): void
    {
        self::getContainer()->get('event_dispatcher')->addListener(EmailEvents::ON_TRANSPORT_WEBHOOK, fn () => null /* exists but does nothing */);
        $this->client->request('POST', '/mailer/callback');

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        Assert::assertSame('No email transport that could process this callback was found', $this->client->getResponse()->getContent());
    }

    public function testMailerCallbackWhenTransportProccessesIt(): void
    {
        self::getContainer()->get('event_dispatcher')->addListener(EmailEvents::ON_TRANSPORT_WEBHOOK, fn (TransportWebhookEvent $event) => $event->setResponse(new Response('OK')));
        $this->client->request('POST', '/mailer/callback');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testUnsubscribeFormActionWithoutTheme(): void
    {
        $form = $this->getForm(null);

        $stat = $this->getStat($form);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), var_export($this->client->getResponse()->getContent(), true));

        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testContactPreferencesLandingPageTracking(): void
    {
        $this->logoutUser();
        $lead                 = $this->createLead();
        $preferenceCenterPage = $this->getPreferencesCenterLandingPage();
        $stat                 = $this->getStat(null, $lead, $preferenceCenterPage);

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        $this->em->clear(Page::class);

        $entity = $this->em->getRepository(Page::class)->getEntity($stat->getEmail()->getPreferenceCenter()->getId());
        $this->assertSame(1, $entity->getHits(), $this->client->getResponse()->getContent());
    }

    public function testContactPreferencesSaveMessage(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $form = $crawler->filter('form')->form();

        // Unsubscribe from email.
        $form->setValues(['lead_contact_frequency_rules[lead_channels][subscribed_channels][0]' => false]);

        $this->assertStringContainsString('/email/unsubscribe/tracking_hash_unsubscribe_form_email', $form->getUri());
        $crawler = $this->client->submit($form);

        self::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $this->assertEquals(1, $crawler->filter('#success-message-text')->count());
        $expectedMessage = static::getContainer()->get('translator')->trans('mautic.email.preferences_center_success_message.text');
        $this->assertEquals($expectedMessage, trim($crawler->filter('#success-message-text')->text(null, false)));
        $this->assertTrue($this->client->getResponse()->isOk());

        // Assert that the contact has the DNC record now.
        $dncRepository = $this->em->getRepository(DoNotContact::class);
        \assert($dncRepository instanceof DoNotContactRepository);
        $dncRecords = $dncRepository->findBy(['lead' => $lead->getId()]);
        Assert::assertCount(1, $dncRecords);
        Assert::assertSame(DoNotContact::UNSUBSCRIBED, $dncRecords[0]->getReason());
        Assert::assertSame('email', $dncRecords[0]->getChannel());
        Assert::assertSame($stat->getEmail()->getId(), $dncRecords[0]->getChannelId());
    }

    public function testUnsubscribeFormActionWithThemeWithoutFormSupport(): void
    {
        $form = $this->getForm('aurora');

        $stat = $this->getStat($form);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testUnsubscribeFormActionWithThemeWithFormSupport(): void
    {
        $form = $this->getForm('blank');

        $stat = $this->getStat($form);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testWithoutUnsubscribeFormAction(): void
    {
        $this->getForm('blank');

        $stat = $this->getStat();

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringNotContainsString('form/submit?formId=', $crawler->html());
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testOneClickUnsubscribeAction(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();
        $this->client->request('POST', '/email/unsubscribe/'.$stat->getTrackingHash(), [
            'List-Unsubscribe' => 'One-Click',
        ]);
        $this->assertTrue($this->client->getResponse()->isOk());
        $dncCollection = $stat->getLead()->getDoNotContact();
        $this->assertEquals(1, $dncCollection->count());
        $this->assertEquals(DoNotContact::UNSUBSCRIBED, $dncCollection->first()->getReason());
    }

    public function testUnsubscribeActionWithCustomPreferenceCenterHasCsrfToken(): void
    {
        $this->logoutUser();
        $lead              = $this->createLead();
        $preferencesCenter = $this->createCustomPreferencesPage('{segmentlist}{saveprefsbutton}');
        $stat              = $this->getStat(null, $lead, $preferencesCenter);
        $this->em->flush();
        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $this->assertResponseIsSuccessful();
        $tokenInput = $crawler->filter('input[name="lead_contact_frequency_rules[_token]"]');
        $this->assertEquals(1, $tokenInput->count(), $this->client->getResponse()->getContent());
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

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Save preferences', $crawler->html());
    }

    /**
     * @return iterable<string, array{contactLocale: string|null, pageLocale: string|null, expectedLocale: string}>
     */
    public function dataForTestUnsubscribeFormActionWithUsingLandingPage(): iterable
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

    /**
     * @dataProvider dataForTestUnsubscribeFormActionWithUsingLandingPage
     */
    public function testUnsubscribeFormActionWithUsingLandingPage(?string $contactLocale, ?string $pageLocale, string $expectedLocale): void
    {
        $lead = $this->createLead($contactLocale);
        $page = $this->createPage($pageLocale);

        $stat = $this->getStat(null, $lead, $page);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $this->assertTrue($this->client->getResponse()->isOk());

        $translator = static::getContainer()->get('translator');
        $needle     = $translator->trans('mautic.page.form.saveprefs', [], null, $expectedLocale);

        $this->assertStringContainsString($needle, $crawler->html());
    }

    /**
     * @throws ORMException
     */
    protected function getStat(Form $form = null, Lead $lead = null, Page $preferenceCenter = null): Stat
    {
        $trackingHash = 'tracking_hash_unsubscribe_form_email';
        $emailName    = 'Test unsubscribe form email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
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
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
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
        $this->assertTrue($this->client->getResponse()->isOk());
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
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString($message, $clientResponse->getContent());
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
        $coreParametersHelper   = self::getContainer()->get('mautic.helper.core_parameters');
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
            'ok_right_stat_hash' => [
                $rightStatHash,
                $wrongEmail,
                $wrongHash,
                'We are sorry to see you go!',
                true,
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
        $this->client->request(Request::METHOD_GET, '/email/unsubscribe/non-existant-hash');
        Assert::assertStringContainsString(
            'Record not found.',
            strip_tags((string) $this->client->getResponse()->getContent())
        );
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
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

        $this->client->request(Request::METHOD_GET, '/email/unsubscribe/existing-tracking-hash');

        Assert::assertStringContainsString(
            'We are sorry to see you go! john@doe.email will no longer receive emails from us. If this was by mistake, click here to re-subscribe.',
            strip_tags((string) $this->client->getResponse()->getContent())
        );
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var DoNotContactRepository $dncRepository */
        $dncRepository = $this->em->getRepository(DoNotContact::class);

        /** @var DoNotContact[] $dncRecords */
        $dncRecords = $dncRepository->findAll();

        Assert::assertCount(1, $dncRecords);
        Assert::assertSame($contact->getId(), $dncRecords[0]->getLead()->getId());
        Assert::assertSame('email', $dncRecords[0]->getChannel());
        Assert::assertSame((int) $email->getId(), (int) $dncRecords[0]->getChannelId());
        Assert::assertSame('User unsubscribed.', $dncRecords[0]->getComments());
    }

    public function testUnsubscribeAllFromPreferencesPage(): void
    {
        // Create a lead and email stat
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();

        // Get the unsubscribe page
        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        // Assert that the response is OK
        self::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Assert that the link for unsubscribe all exists
        $unsubscribeAllLink = $crawler->filter('a[href^="/email/dnc/"]')->first();
        $this->assertCount(1, $unsubscribeAllLink, 'Unsubscribe all link not found');
        $href = $unsubscribeAllLink->attr('href');

        // Click the link for unsubscribe all
        $this->client->request('GET', $href);

        // Assert that the response is OK
        self::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Assert that the response contains the expected string
        $this->assertStringContainsString(
            'We are sorry to see you go! john@doe.email will no longer receive emails from us',
            $this->client->getResponse()->getContent()
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
}
