<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
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

        $this->logoutUser();
        $this->submitForm($formId, ['mauticform[email]' => 'visitor@example.com'], $server);

        $cookieJar = $this->client->getCookieJar();
        $this->assertSame($expectTracking, $cookieJar->get('mautic_device_id') instanceof Cookie);
        $this->assertSame($expectTracking, $cookieJar->get('mtc_id') instanceof Cookie);

        $this->em->clear();
        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        $submission = $submissions[0];

        $this->assertSame($expectTracking, null !== $submission->getIpAddress());
        $this->assertSame($expectTracking, null !== $submission->getTrackingId());

        // The submitted data is still saved to the contact
        $contact = $submission->getLead();
        $this->assertSame('visitor@example.com', $contact?->getEmail());
        $this->assertSame($expectTracking, !$contact->getIpAddresses()->isEmpty());
        $this->assertSame($expectTracking, [] !== $this->em->getRepository(LeadDevice::class)->findBy(['lead' => $contact]));
    }

    public function testAnonymousSubmissionWithPrivacySignalStoresNoIpAddress(): void
    {
        // Without a contact field the submission goes to an anonymous contact
        $formId = $this->createForm(['label' => 'Comment', 'type' => 'text', 'alias' => 'comment']);

        $this->logoutUser();
        $this->submitForm($formId, ['mauticform[comment]' => 'Hello'], ['HTTP_DNT' => '1']);

        $this->assertNotInstanceOf(Cookie::class, $this->client->getCookieJar()->get('mautic_device_id'));

        $this->em->clear();
        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $formId]);
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertNotInstanceOf(IpAddress::class, $submission->getIpAddress());
        $this->assertInstanceOf(Lead::class, $submission->getLead());
        $this->assertTrue($submission->getLead()->getIpAddresses()->isEmpty());
    }

    /**
     * @param array<string, string> $field
     */
    private function createForm(array $field): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'        => 'Trackability form',
            'formType'    => 'standalone',
            'isPublished' => true,
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
