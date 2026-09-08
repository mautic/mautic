<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SmsApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testContinueSendingCanBeReadAndWrittenThroughTheApi(): void
    {
        $segment = $this->createSegment('sms-api-schedule');

        $this->client->request(Request::METHOD_POST, '/api/smses/new', [
            'name'        => 'API scheduled SMS',
            'message'     => 'Scheduled message',
            'smsType'     => 'list',
            'lists'       => [$segment->getId()],
            'isPublished' => true,
            'publishUp'   => '2030-01-01 10:00',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $sms = $this->getResponseSms();
        self::assertFalse($sms['continueSending']);
        self::assertNull($sms['publishDown']);
        $smsId = (int) $sms['id'];

        $this->client->request(Request::METHOD_PATCH, "/api/smses/{$smsId}/edit", [
            'continueSending' => true,
            'publishUp'       => '2030-01-01 10:00',
            'publishDown'     => '2030-01-02 10:00',
        ]);

        self::assertResponseIsSuccessful();
        $sms = $this->getResponseSms();
        self::assertTrue($sms['continueSending']);
        self::assertSame('2030-01-01T10:00:00+00:00', $sms['publishUp']);
        self::assertSame('2030-01-02T10:00:00+00:00', $sms['publishDown']);

        $this->client->request(Request::METHOD_PATCH, "/api/smses/{$smsId}/edit", [
            'description' => 'Continue sending was omitted from this update',
        ]);

        self::assertResponseIsSuccessful();
        $sms = $this->getResponseSms();
        self::assertTrue($sms['continueSending']);
        self::assertSame('2030-01-02T10:00:00+00:00', $sms['publishDown']);

        $this->client->request(Request::METHOD_PATCH, "/api/smses/{$smsId}/edit", [
            'continueSending' => false,
            'publishDown'     => '2030-01-03 10:00',
        ]);

        self::assertResponseIsSuccessful();
        $sms = $this->getResponseSms();
        self::assertFalse($sms['continueSending']);
        self::assertNull($sms['publishDown']);

        $this->client->request(Request::METHOD_GET, "/api/smses/{$smsId}");

        self::assertResponseIsSuccessful();
        $sms = $this->getResponseSms();
        self::assertFalse($sms['continueSending']);
        self::assertNull($sms['publishDown']);
    }

    public function testContinueSendingRejectsAnInvalidDateRange(): void
    {
        $sms = $this->createSms('invalid-api-schedule');
        $this->em->flush();

        $this->client->request(Request::METHOD_PATCH, "/api/smses/{$sms->getId()}/edit", [
            'continueSending' => true,
            'publishUp'       => '2030-01-02 10:00',
            'publishDown'     => '2030-01-01 10:00',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = $this->getJsonResponse();
        self::assertNotEmpty($response['errors']);
    }

    public function testContinueSendingWriteRequiresPublishPermission(): void
    {
        $owner = $this->getUser('sales');
        self::assertInstanceOf(User::class, $owner);

        $sms = $this->createSms('api-schedule-permission');
        $sms->setCreatedBy($owner->getId());
        $this->em->flush();

        $this->setPermission($owner->getRole(), ['sms:smses' => ['editown']]);
        $this->loginUser($owner);
        $this->client->setServerParameter('PHP_AUTH_USER', $owner->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_PATCH, "/api/smses/{$sms->getId()}/edit", [
            'continueSending' => true,
            'publishUp'       => '2030-01-01 10:00',
            'publishDown'     => '2030-01-02 10:00',
        ]);

        self::assertResponseIsSuccessful();
        $sms = $this->getResponseSms();
        self::assertFalse($sms['continueSending']);
        self::assertNull($sms['publishUp']);
        self::assertNull($sms['publishDown']);
    }

    private function createSegment(string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setIsPublished(true);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createSms(string $name): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage('Scheduled message');
        $sms->setSmsType('list');
        $sms->setIsPublished(true);
        $sms->addList($this->createSegment($name));
        $this->em->persist($sms);

        return $sms;
    }

    private function getUser(string $username): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function setPermission(Role $role, array $permissions): void
    {
        $roleModel = self::getContainer()->get(RoleModel::class);
        self::assertInstanceOf(RoleModel::class, $roleModel);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponseSms(): array
    {
        $response = $this->getJsonResponse();
        self::assertArrayHasKey('sms', $response);
        self::assertIsArray($response['sms']);

        return $response['sms'];
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);
        $response = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($response);

        return $response;
    }
}
