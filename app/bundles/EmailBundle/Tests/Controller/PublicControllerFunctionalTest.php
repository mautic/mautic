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
        if (in_array($this->getName(), self::UNSUBSCRIBE_TESTS)) {
            $this->configParams['show_contact_preferences'] = 0;
        } else {
            $this->configParams['show_contact_preferences'] = 1;
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

        $this->assertStringContainsString('/email/unsubscribe/tracking_hash_unsubscribe_form_email', $crawler->filter('form')->eq(0)->attr('action'));
        $crawler = $this->client->submitForm('Save');

        self::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $this->assertEquals(1, $crawler->filter('#success-message-text')->count());
        $expectedMessage = static::getContainer()->get('translator')->trans('mautic.email.preferences_center_success_message.text');
        $this->assertEquals($expectedMessage, trim($crawler->filter('#success-message-text')->text(null, false)));
        $this->assertTrue($this->client->getResponse()->isOk());
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
        $lead              = $this->createLead();
        $preferencesCenter = $this->createCustomPreferencesPage('{segmentlist}{saveprefsbutton}');
        $stat              = $this->getStat(null, $lead, $preferencesCenter);
        $this->em->flush();
        $crawler    = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $tokenInput = $crawler->filter('input[name="lead_contact_frequency_rules[_token]"]');
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
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

    protected function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('john@doe.email');
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

    public function testPreviewForExpiredEmail(): void
    {
        $emailName    = 'Test preview email';

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
        $this->assertFalse($this->client->getResponse()->isOk());
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
        bool $addedRow
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
        $coreParametersHelper   = self::$container->get('mautic.helper.core_parameters');
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
}
