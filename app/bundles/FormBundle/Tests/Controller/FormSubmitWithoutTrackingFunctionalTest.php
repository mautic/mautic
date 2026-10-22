<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Cookie as ResponseCookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * With forms set to submit without tracking, no form tracks the submitter, the same as kiosk mode and DNT/GPC.
 */
final class FormSubmitWithoutTrackingFunctionalTest extends MauticMysqlTestCase
{
    private const TRACKING_COOKIES = ['mautic_device_id', 'mtc_id', 'mautic_referer_id'];

    private const EXISTING_TRACKING_ID = 'existingtrackingid000000';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['form_submit_without_tracking'] = 'testTrackedVisitorIsStillTrackedWhileTheOptionIsOff' !== $this->name();
        $this->configParams['form_field_autofill']          = true;

        parent::setUp();
    }

    /**
     * Run separately because the script embed defines the global MAUTIC_NON_TRACKABLE_REQUEST constant.
     */
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExistingTrackingCookiesAreIgnoredWhileTheOptionIsOn(): void
    {
        $formId         = $this->createForm();
        $trackedContact = $this->createTrackedVisitor();

        // Loading the form neither resolves the tracked contact to fill in its name nor refreshes the cookies
        foreach (['/form/%d', '/form/embed/%d', '/form/generate.js?id=%d'] as $url) {
            $this->client->request(Request::METHOD_GET, sprintf($url, $formId));
            $this->assertResponseIsSuccessful();
            $this->assertStringNotContainsString('Trackedvisitor', (string) $this->client->getResponse()->getContent(), $url);
            $this->assertNoTrackingCookie($this->client->getResponse()->headers->getCookies());
        }

        $this->submitForm($formId, 'visitor@example.com');
        $this->assertNoTrackingCookie($this->client->getResponse()->headers->getCookies());

        // The submission is not linked to the tracked contact, which stays anonymous
        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertNull($submission->getTrackingId());
        $this->assertSame('visitor@example.com', $submission->getLead()?->getEmail());
        $this->assertNotSame($trackedContact->getId(), $submission->getLead()->getId());
        $this->assertNull($this->em->find(Lead::class, $trackedContact->getId())?->getEmail());
    }

    public function testAjaxSubmissionIsNotLinkedToTheTrackedContactWhileTheOptionIsOn(): void
    {
        $formId         = $this->createForm();
        $trackedContact = $this->createTrackedVisitor();

        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $form    = $crawler->filter('form[id=mauticform_withouttrackingform]')->form();
        $form->setValues(['mauticform[email]' => 'visitor@example.com']);
        $this->client->request(Request::METHOD_POST, '/form/submit/ajax', $form->getPhpValues(), [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_decode((string) $this->client->getResponse()->getContent(), true)['success']);
        $this->assertNoTrackingCookie($this->client->getResponse()->headers->getCookies());

        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertNotInstanceOf(IpAddress::class, $submission->getIpAddress());
        $this->assertSame('visitor@example.com', $submission->getLead()?->getEmail());
        $this->assertNotSame($trackedContact->getId(), $submission->getLead()->getId());
    }

    public function testFormWithoutContactFieldsCreatesNoContactWhileTheOptionIsOn(): void
    {
        $formId = $this->createForm([['label' => 'Comment', 'type' => 'text', 'alias' => 'comment']]);

        $this->logoutUser();
        $this->client->getCookieJar()->clear();
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $form    = $crawler->filter('form[id=mauticform_withouttrackingform]')->form();
        $form->setValues(['mauticform[comment]' => 'Hello']);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertNoTrackingCookie($this->client->getCookieJar()->all());

        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $resultsTable = $this->connection->fetchOne('SHOW TABLES LIKE ?', [MAUTIC_TABLE_PREFIX."form_results\\_{$formId}\\_%"]);
        $results      = $this->connection->fetchFirstColumn("SELECT comment FROM {$resultsTable} WHERE submission_id = ?", [$submission->getId()]);
        $this->assertSame(['Hello'], $results);
        $this->assertNotInstanceOf(Lead::class, $submission->getLead());
        $this->assertSame([], $this->em->getRepository(Lead::class)->findAll());
    }

    public function testTrackedVisitorIsStillTrackedWhileTheOptionIsOff(): void
    {
        $formId         = $this->createForm();
        $trackedContact = $this->createTrackedVisitor();

        // Loading the form resolves the tracked contact to fill in its name and refreshes the cookies
        $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Trackedvisitor', (string) $this->client->getResponse()->getContent());
        $this->assertContains('mautic_device_id', $this->getCookieNames($this->client->getResponse()->headers->getCookies()));

        $this->submitForm($formId, 'visitor@example.com');

        // The submission identifies the tracked contact, like before
        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertInstanceOf(IpAddress::class, $submission->getIpAddress());
        $this->assertSame($trackedContact->getId(), $submission->getLead()?->getId());
        $this->assertSame('visitor@example.com', $submission->getLead()->getEmail());
    }

    /**
     * A visitor tracked before forms were set to submit without tracking still sends the tracking cookies.
     */
    private function createTrackedVisitor(): Lead
    {
        $contact = new Lead();
        $contact->setFirstname('Trackedvisitor');
        $this->em->persist($contact);

        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId(self::EXISTING_TRACKING_ID);
        $device->setDateAdded(new \DateTime());
        $this->em->persist($device);
        $this->em->flush();

        $this->logoutUser();
        $cookieJar = $this->client->getCookieJar();
        $cookieJar->clear();
        $cookieJar->set(new Cookie('mautic_device_id', self::EXISTING_TRACKING_ID));
        $cookieJar->set(new Cookie('mtc_id', (string) $contact->getId()));

        return $contact;
    }

    /**
     * @param array<Cookie|ResponseCookie> $cookies
     */
    private function assertNoTrackingCookie(array $cookies): void
    {
        $this->assertSame([], array_values(array_intersect(self::TRACKING_COOKIES, $this->getCookieNames($cookies))));
    }

    /**
     * @param array<Cookie|ResponseCookie> $cookies
     *
     * @return list<string>
     */
    private function getCookieNames(array $cookies): array
    {
        return array_values(array_map(static fn (Cookie|ResponseCookie $cookie): string => $cookie->getName(), $cookies));
    }

    /**
     * @param list<array<string, mixed>>|null $fields
     */
    private function createForm(?array $fields = null): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'        => 'Without tracking form',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => $fields ?? [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                    'isAutoFill'   => true,
                ],
            ],
            // Progressive profiling also looks up the visitor while rendering
            'progressiveProfilingLimit' => 2,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, (string) $this->client->getResponse()->getContent());

        return (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];
    }

    private function submitForm(int $formId, string $email): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[id=mauticform_withouttrackingform]')->form();
        $form->setValues(['mauticform[email]' => $email]);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }
}
