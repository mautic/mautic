<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\Point;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class KioskModeSubmissionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @return iterable<string, array{bool, bool}>
     */
    public static function kioskModeProvider(): iterable
    {
        yield 'kiosk mode does not store the IP or device' => [true, false];
        yield 'regular form stores the IP and device' => [false, true];
    }

    #[DataProvider('kioskModeProvider')]
    public function testSubmissionStoresIpAddressAndDeviceIdOnlyOutsideKioskMode(bool $inKioskMode, bool $expectTracking): void
    {
        $formId = $this->createForm($inKioskMode);

        $this->logoutUser();
        $this->submitForm($formId, 'kiosk@example.com');

        $this->em->clear();
        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        $submission = $submissions[0];

        $this->assertSame($expectTracking, null !== $submission->getIpAddress());
        $this->assertSame($expectTracking, null !== $submission->getTrackingId());
        $this->assertSame('kiosk@example.com', $submission->getLead()?->getEmail());
    }

    #[DataProvider('kioskModeProvider')]
    public function testIpAddressIsSavedOnlyOutsideKioskMode(bool $inKioskMode, bool $expectTracking): void
    {
        $formId = $this->createForm($inKioskMode);
        $this->createFormSubmitPointAction($formId);

        $this->logoutUser();
        $this->submitForm($formId, 'kiosk@example.com', '203.0.113.7');

        $this->em->clear();
        $ipAddress = $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.7']);
        $this->assertSame($expectTracking, null !== $ipAddress);

        $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => 'kiosk@example.com']);
        $this->assertInstanceOf(Lead::class, $contact);
        $this->assertSame(5, $contact->getPoints());

        $pointsChangeLog = $this->em->getRepository(PointsChangeLog::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(PointsChangeLog::class, $pointsChangeLog);
        $this->assertSame($expectTracking, null !== $pointsChangeLog->getIpAddress());

        $pointLog = $this->em->getRepository(LeadPointLog::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(LeadPointLog::class, $pointLog);
        $this->assertSame($expectTracking, null !== $pointLog->getIpAddress());
    }

    private function createFormSubmitPointAction(int $formId): void
    {
        $point = new Point();
        $point->setName('Submits the kiosk form');
        $point->setType('form.submit');
        $point->setDelta(5);
        $point->setRepeatable(false);
        $point->setIsPublished(true);
        $point->setProperties(['forms' => [$formId]]);
        $this->em->persist($point);
        $this->em->flush();
    }

    private function createForm(bool $inKioskMode): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'        => 'Kiosk form',
            'formType'    => 'standalone',
            'isPublished' => true,
            'inKioskMode' => $inKioskMode,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, (string) $this->client->getResponse()->getContent());

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame($inKioskMode, (bool) ($response['form']['inKioskMode'] ?? false));

        return (int) $response['form']['id'];
    }

    private function submitForm(int $formId, string $email, string $remoteAddress = '127.0.0.1'): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[id=mauticform_kioskform]')->form();
        $form->setValues(['mauticform[email]' => $email]);
        $this->client->submit($form, [], ['REMOTE_ADDR' => $remoteAddress]);
        $this->assertResponseIsSuccessful();
    }
}
