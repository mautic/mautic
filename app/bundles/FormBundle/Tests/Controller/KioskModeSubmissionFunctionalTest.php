<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
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

    private function submitForm(int $formId, string $email): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[id=mauticform_kioskform]')->form();
        $form->setValues(['mauticform[email]' => $email]);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }
}
