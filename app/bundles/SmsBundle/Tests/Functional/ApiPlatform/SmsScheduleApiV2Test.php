<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional\ApiPlatform;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Tests\Functional\ApiPlatform\OwnershipScopedApiAuthorizationTestBase;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SmsScheduleApiV2Test extends OwnershipScopedApiAuthorizationTestBase
{
    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('schedulePayloadProvider')]
    public function testPatchAppliesTheScheduleIndependentlyOfPayloadOrder(array $payload): void
    {
        $user = $this->createApiUser('schedule-owner', ['viewown', 'editown', 'publishown']);
        $sms  = $this->createSegmentSms('ordered-schedule', $user);
        $id   = (int) $sms->getId();
        $this->loginAsApiUser($user);

        $this->requestJson(Request::METHOD_PATCH, "/api/v2/sms/{$id}", $payload);

        self::assertResponseIsSuccessful();
        $this->em->clear();

        $updatedSms = $this->em->find(Sms::class, $id);
        self::assertInstanceOf(Sms::class, $updatedSms);
        self::assertTrue($updatedSms->isContinueSending());
        self::assertSame('2030-01-02 10:00:00+00:00', $updatedSms->getPublishDown()?->format('Y-m-d H:i:sP'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function schedulePayloadProvider(): iterable
    {
        yield 'publishDown before continueSending' => [[
            'publishUp'       => '2030-01-01T10:00:00+00:00',
            'publishDown'     => '2030-01-02T10:00:00+00:00',
            'continueSending' => true,
        ]];

        yield 'continueSending before publishDown' => [[
            'publishUp'       => '2030-01-01T10:00:00+00:00',
            'continueSending' => true,
            'publishDown'     => '2030-01-02T10:00:00+00:00',
        ]];
    }

    public function testPutPreservesTheSubmittedStopDateWhenContinuingIsEnabled(): void
    {
        $user    = $this->createApiUser('put-owner', ['viewown', 'editown', 'publishown']);
        $segment = $this->createSegment('put-schedule-segment');
        $sms     = $this->createSegmentSms('put-schedule', $user, $segment);
        $id      = (int) $sms->getId();
        $this->loginAsApiUser($user);

        $this->requestJson(Request::METHOD_PUT, "/api/v2/sms/{$id}", [
            'name'            => 'Updated PUT schedule',
            'message'         => 'Updated scheduled message',
            'smsType'         => 'list',
            'lists'           => ["/api/v2/segments/{$segment->getId()}"],
            'publishUp'       => '2030-01-01T10:00:00+00:00',
            'publishDown'     => '2030-01-02T10:00:00+00:00',
            'continueSending' => true,
        ]);

        self::assertResponseIsSuccessful();
        $this->em->clear();

        $updatedSms = $this->em->find(Sms::class, $id);
        self::assertInstanceOf(Sms::class, $updatedSms);
        self::assertTrue($updatedSms->isContinueSending());
        self::assertSame('2030-01-02 10:00:00+00:00', $updatedSms->getPublishDown()?->format('Y-m-d H:i:sP'));
    }

    public function testPutCannotCancelAScheduleByOmittingItWithoutPublishPermission(): void
    {
        $user    = $this->createApiUser('put-edit-only-owner', ['viewown', 'editown']);
        $segment = $this->createSegment('put-protected-schedule-segment');
        $sms     = $this->createSegmentSms('put-protected-schedule', $user, $segment);
        $sms->setPublishUp(new \DateTime('2030-01-01T10:00:00+00:00'));
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('2030-01-02T10:00:00+00:00'));
        $this->em->persist($sms);
        $this->em->flush();
        $id = (int) $sms->getId();
        $this->em->clear();
        $persistedSms = $this->em->find(Sms::class, $id);
        self::assertInstanceOf(Sms::class, $persistedSms);
        self::assertTrue($persistedSms->isContinueSending());
        self::assertSame('2030-01-01 10:00:00+00:00', $persistedSms->getPublishUp()?->format('Y-m-d H:i:sP'));
        self::assertSame('2030-01-02 10:00:00+00:00', $persistedSms->getPublishDown()?->format('Y-m-d H:i:sP'));
        $this->em->clear();
        $this->loginAsApiUser($user);

        $this->requestJson(Request::METHOD_PUT, "/api/v2/sms/{$id}", [
            'name'    => 'Attempted schedule cancellation',
            'message' => 'The schedule fields are intentionally omitted.',
            'smsType' => 'list',
            'lists'   => ["/api/v2/segments/{$segment->getId()}"],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->em->clear();

        $unchangedSms = $this->em->find(Sms::class, $id);
        self::assertInstanceOf(Sms::class, $unchangedSms);
        self::assertTrue($unchangedSms->isContinueSending());
        self::assertSame('2030-01-01 10:00:00+00:00', $unchangedSms->getPublishUp()?->format('Y-m-d H:i:sP'));
        self::assertSame('2030-01-02 10:00:00+00:00', $unchangedSms->getPublishDown()?->format('Y-m-d H:i:sP'));
    }

    public function testFalseContinuingModeClearsAStopDateSubmittedAfterIt(): void
    {
        $user = $this->createApiUser('one-time-owner', ['viewown', 'editown', 'publishown']);
        $sms  = $this->createSegmentSms('one-time-schedule', $user);
        $sms->setContinueSending(true);
        $sms->setPublishDown(new \DateTime('2030-01-02T10:00:00+00:00'));
        $this->em->flush();
        $id = (int) $sms->getId();
        $this->loginAsApiUser($user);

        $this->requestJson(Request::METHOD_PATCH, "/api/v2/sms/{$id}", [
            'continueSending' => false,
            'publishDown'     => '2030-01-03T10:00:00+00:00',
        ]);

        self::assertResponseIsSuccessful();
        $this->em->clear();

        $updatedSms = $this->em->find(Sms::class, $id);
        self::assertInstanceOf(Sms::class, $updatedSms);
        self::assertFalse($updatedSms->isContinueSending());
        self::assertNull($updatedSms->getPublishDown());
    }

    public function testScheduleFieldsRequirePublishPermissionButOrdinaryEditsDoNot(): void
    {
        $user = $this->createApiUser('edit-only-owner', ['viewown', 'editown']);
        $sms  = $this->createSegmentSms('permission-schedule', $user);
        $id   = (int) $sms->getId();
        $this->loginAsApiUser($user);

        $this->requestJson(Request::METHOD_PATCH, "/api/v2/sms/{$id}", [
            'description' => 'An edit-only user can change ordinary fields.',
        ]);
        self::assertResponseIsSuccessful();

        foreach ([
            ['isPublished' => false],
            ['publishUp' => '2030-01-01T10:00:00+00:00'],
            ['publishDown' => '2030-01-02T10:00:00+00:00'],
            ['continueSending' => true],
        ] as $schedulePayload) {
            $this->requestJson(Request::METHOD_PATCH, "/api/v2/sms/{$id}", $schedulePayload);
            self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }

        $this->em->clear();
        $updatedSms = $this->em->find(Sms::class, $id);
        self::assertInstanceOf(Sms::class, $updatedSms);
        self::assertSame('An edit-only user can change ordinary fields.', $updatedSms->getDescription());
        self::assertTrue($updatedSms->getIsPublished());
        self::assertNull($updatedSms->getPublishUp());
        self::assertNull($updatedSms->getPublishDown());
        self::assertFalse($updatedSms->isContinueSending());
    }

    public function testPublishingAnotherUsersScheduleRequiresPublishOther(): void
    {
        $owner = $this->createApiUser('foreign-owner', ['viewown', 'editown', 'publishown']);
        $sms   = $this->createSegmentSms('foreign-schedule', $owner);
        $id    = (int) $sms->getId();

        $ownPublisher = $this->createApiUser('own-publisher', ['viewother', 'editother', 'publishown']);
        $this->loginAsApiUser($ownPublisher);
        $this->requestJson(Request::METHOD_PATCH, "/api/v2/sms/{$id}", [
            'publishUp' => '2030-01-01T10:00:00+00:00',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPublishOtherAllowsPublishingAnotherUsersSchedule(): void
    {
        $owner = $this->createApiUser('publish-other-owner', ['viewown', 'editown', 'publishown']);
        $sms   = $this->createSegmentSms('publish-other-schedule', $owner);
        $id    = (int) $sms->getId();

        $otherPublisher = $this->createApiUser('other-publisher', ['viewother', 'editother', 'publishother']);
        $this->loginAsApiUser($otherPublisher);
        $this->requestJson(Request::METHOD_PATCH, "/api/v2/sms/{$id}", [
            'publishUp' => '2030-01-01T10:00:00+00:00',
        ]);
        self::assertResponseIsSuccessful();
    }

    public function testPostRequiresPublishPermissionForPublishedState(): void
    {
        $user = $this->createApiUser('create-only', ['create']);
        $this->loginAsApiUser($user);

        $this->requestJson(Request::METHOD_POST, '/api/v2/sms', [
            'name'        => 'Ordinary draft API SMS',
            'message'     => 'The SMS is explicitly created as a draft.',
            'smsType'     => 'template',
            'isPublished' => false,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->requestJson(Request::METHOD_POST, '/api/v2/sms', [
            'name'    => 'Implicitly published API SMS',
            'message' => 'The entity default would publish this SMS.',
            'smsType' => 'template',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @param string[] $smsPermissions
     */
    private function createApiUser(string $username, array $smsPermissions): User
    {
        return $this->createUserWithPermissions(
            username: $username,
            email: "{$username}@example.test",
            password: 'Maut1cR0cks!',
            permissions: [
                'sms:smses' => $smsPermissions,
                'lead:lists' => ['viewother'],
                'api:access' => ['full'],
            ],
        );
    }

    private function loginAsApiUser(User $user): void
    {
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }

    private function createSegmentSms(string $name, User $owner, ?LeadList $segment = null): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage('Scheduled message');
        $sms->setSmsType('list');
        $sms->setIsPublished(true);
        $sms->setCreatedBy($owner);
        $sms->addList($segment ?? $this->createSegment($name.'-segment'));
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }

    private function createSegment(string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestJson(string $method, string $uri, array $payload): void
    {
        $contentType = Request::METHOD_PATCH === $method ? 'application/merge-patch+json' : 'application/ld+json';

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            [
                'CONTENT_TYPE' => $contentType,
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
