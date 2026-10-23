<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\Point;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A visitor who sends a privacy signal can still submit a form, but is not tracked.
 */
final class FormSubmissionTrackabilityFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @return iterable<string, array{array<string, string>, bool}>
     */
    public static function trackabilityProvider(): iterable
    {
        yield 'Do Not Track' => [['HTTP_DNT' => '1'], false];
        yield 'Global Privacy Control' => [['HTTP_SEC_GPC' => '1'], false];
        yield 'trackable visitor' => [[], true];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('trackabilityProvider')]
    public function testSubmissionTracksTheVisitorOnlyWhenTheRequestIsTrackable(array $server, bool $expectTracking): void
    {
        $formId = $this->createForm([
            'label'        => 'Email',
            'type'         => 'email',
            'alias'        => 'email',
            'mappedObject' => 'contact',
            'mappedField'  => 'email',
        ]);
        $this->createFormSubmitPointAction($formId);

        // The visitor is sent back to the referer after submitting, so it points at the form page itself
        $page    = "http://localhost/form/{$formId}";
        $referer = $page.'?email=visitor%40example.com&utm_source=newsletter#form';

        $this->logoutUser();
        $this->submitForm($formId, ['mauticform[email]' => 'visitor@example.com'], $server + ['REMOTE_ADDR' => '203.0.113.9', 'HTTP_REFERER' => $referer]);

        $cookieJar = $this->client->getCookieJar();
        $this->assertSame($expectTracking, $cookieJar->get('mautic_device_id') instanceof Cookie);
        $this->assertSame($expectTracking, $cookieJar->get('mtc_id') instanceof Cookie);

        $this->em->clear();
        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        $submission = $submissions[0];

        $this->assertSame($expectTracking, null !== $submission->getIpAddress());
        $this->assertSame($expectTracking, null !== $submission->getTrackingId());

        // The query string of the page can carry identifiers, so only the page address is kept for an untracked visitor
        $this->assertSame($expectTracking ? $referer : $page, $submission->getReferer());

        // The submitted data is still saved to the contact
        $contact = $submission->getLead();
        $this->assertSame('visitor@example.com', $contact?->getEmail());
        $this->assertSame($expectTracking, !$contact->getIpAddresses()->isEmpty());
        $this->assertSame($expectTracking, [] !== $this->em->getRepository(LeadDevice::class)->findBy(['lead' => $contact]));

        // The visitor's IP address is not saved anywhere, not even on its own
        $this->assertSame($expectTracking, $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.9']) instanceof IpAddress);

        // The visitor still gets the points, the points log just has no IP
        $this->assertSame(5, $contact->getPoints());
        $pointsChangeLog = $this->em->getRepository(PointsChangeLog::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(PointsChangeLog::class, $pointsChangeLog);
        $this->assertSame($expectTracking, null !== $pointsChangeLog->getIpAddress());
        $pointLog = $this->em->getRepository(LeadPointLog::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(LeadPointLog::class, $pointLog);
        $this->assertSame($expectTracking, null !== $pointLog->getIpAddress());
    }

    public function testAnonymousSubmissionWithPrivacySignalCreatesNoContact(): void
    {
        // Without a contact field there is nothing to save to a contact, so an untracked visitor gets none
        $formId = $this->createForm(['label' => 'Comment', 'type' => 'text', 'alias' => 'comment']);

        $this->logoutUser();
        $this->submitForm($formId, ['mauticform[comment]' => 'Hello'], ['HTTP_DNT' => '1']);

        $this->assertNotInstanceOf(Cookie::class, $this->client->getCookieJar()->get('mautic_device_id'));

        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertNotInstanceOf(IpAddress::class, $submission->getIpAddress());
        $this->assertNotInstanceOf(Lead::class, $submission->getLead());
        $this->assertSame([], $this->em->getRepository(Lead::class)->findAll());
    }

    public function testUntrackedVisitorCanSubmitFormWithFieldsHiddenByProgressiveProfiling(): void
    {
        // Only the first field is shown, so the second required field can't block the submission
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'                      => 'Trackability form',
            'formType'                  => 'standalone',
            'isPublished'               => true,
            'inKioskMode'               => 0,
            'progressiveProfilingLimit' => 1,
            'fields'                    => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                    'isRequired'   => true,
                ],
                [
                    'label'        => 'First name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                    'isRequired'   => true,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, (string) $this->client->getResponse()->getContent());
        $formId = (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];

        $this->logoutUser();
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}", [], [], ['HTTP_DNT' => '1']);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('[name="mauticform[firstname]"]'));

        $form = $crawler->filter('form[id=mauticform_trackabilityform]')->form();
        $form->setValues(['mauticform[email]' => 'visitor@example.com']);
        $this->client->submit($form, [], ['HTTP_DNT' => '1']);
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertSame('visitor@example.com', $submission->getLead()?->getEmail());
    }

    /**
     * @return iterable<string, array{bool, array<string, string>, bool}>
     */
    public static function trackingPermissionProvider(): iterable
    {
        yield 'standard form, Do Not Track' => [false, ['HTTP_DNT' => '1'], false];
        yield 'standard form, Global Privacy Control' => [false, ['HTTP_SEC_GPC' => '1'], false];
        yield 'kiosk form, trackable visitor' => [true, [], false];
        yield 'kiosk form, Do Not Track' => [true, ['HTTP_DNT' => '1'], false];
        yield 'standard form, trackable visitor' => [false, [], true];
    }

    /**
     * Evidence for the Privacy Project (2a): no tracking cookie is set before the visitor allows tracking.
     *
     * The cookie jar holds every Set-Cookie header the visitor received while viewing and submitting the form.
     *
     * @param array<string, string> $server
     */
    #[DataProvider('trackingPermissionProvider')]
    public function testNoTrackingCookieIsSetUnlessTrackingIsPermitted(bool $inKioskMode, array $server, bool $trackingPermitted): void
    {
        $formId = $this->createForm([
            'label'        => 'Email',
            'type'         => 'email',
            'alias'        => 'email',
            'mappedObject' => 'contact',
            'mappedField'  => 'email',
        ], $inKioskMode);

        $this->logoutUser();
        $this->client->getCookieJar()->clear();
        $this->submitForm($formId, ['mauticform[email]' => 'visitor@example.com'], $server);

        $cookieNames     = array_map(static fn (Cookie $cookie): string => $cookie->getName(), $this->client->getCookieJar()->all());
        $trackingCookies = array_values(array_intersect(['mautic_device_id', 'mtc_id', 'mautic_referer_id'], $cookieNames));

        $this->assertSame($trackingPermitted ? ['mautic_device_id', 'mtc_id'] : [], $trackingCookies);
    }

    private function createFormSubmitPointAction(int $formId): void
    {
        $point = new Point();
        $point->setName('Submits the form');
        $point->setType('form.submit');
        $point->setDelta(5);
        $point->setRepeatable(false);
        $point->setIsPublished(true);
        $point->setProperties(['forms' => [$formId]]);
        $this->em->persist($point);
        $this->em->flush();
    }

    /**
     * @param array<string, string> $field
     */
    private function createForm(array $field, bool $inKioskMode = false): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'        => 'Trackability form',
            'formType'    => 'standalone',
            'isPublished' => true,
            'inKioskMode' => $inKioskMode,
            'fields'      => [$field],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, (string) $this->client->getResponse()->getContent());

        return (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $server
     */
    private function submitForm(int $formId, array $values, array $server): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}", [], [], $server);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[id=mauticform_trackabilityform]')->form();
        $form->setValues($values);
        $this->client->submit($form, [], $server);
        $this->assertResponseIsSuccessful();
    }
}
