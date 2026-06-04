<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommonApiControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testApiReturnsConflictErrorForLockedEmailEntitySupportingLock(): void
    {
        $email = $this->createEmail('Test Email');

        $this->lockEntityAsAdmin($email);

        $this->createAndAuthenticateApiUser('api_user', 'api@example.com');

        $this->assertNotNull($email->getCheckedOut());
        $this->assertEquals('Admin User', $email->getCheckedOutByUser());

        $this->client->request('PATCH', '/api/emails/'.$email->getId().'/edit', [
            'name' => 'Updated Email',
        ]);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());

        $data  = json_decode($response->getContent(), true);
        $error = $data['errors'][0];

        $this->assertEquals(Response::HTTP_CONFLICT, $error['code']);

        $translator = static::getContainer()->get('translator');
        assert($translator instanceof TranslatorInterface);

        $coreParametersHelper = static::getContainer()->get('mautic.helper.core_parameters');
        assert($coreParametersHelper instanceof CoreParametersHelper);
        $dateFormat = $coreParametersHelper->get('date_format_dateonly');
        $timeFormat = $coreParametersHelper->get('date_format_timeonly');

        $expectedMessage = $translator->trans('mautic.api.error.entity.locked', [
            '%name%' => $email->getName(),
            '%user%' => $email->getCheckedOutByUser(),
            '%date%' => $email->getCheckedOut()->format($dateFormat),
            '%time%' => $email->getCheckedOut()->format($timeFormat),
        ]);

        $this->assertEquals($expectedMessage, $error['message']);
    }

    public function testApiAllowsEditForLockedLeadEntityNotSupportingLock(): void
    {
        $lead = $this->createLead('FirstName', 'LastName', 'nonlock@example.com');

        $this->lockEntityAsAdmin($lead);

        $this->createAndAuthenticateApiUser('api_lead_user', 'api-lead@example.com');

        $this->client->request('PATCH', '/api/contacts/'.$lead->getId().'/edit', [
            'firstname' => 'Updated Firstname',
        ]);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Updated Firstname', $data['contact']['fields']['core']['firstname']['normalizedValue']);
    }

    public function testBatchEditFailsForLockedEmailEntity(): void
    {
        $email = $this->createEmail('Test Email');

        $this->lockEntityAsAdmin($email);

        $this->createAndAuthenticateApiUser('api_user_batch', 'api-batch@example.com');

        $payload = [
            [
                'id'   => $email->getId(),
                'name' => 'Updated Email',
            ],
        ];

        $this->client->request(
            'PATCH',
            '/api/emails/batch/edit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('errors', $data);

        $error = $data['errors'][0];

        $this->assertEquals(Response::HTTP_CONFLICT, $error['code']);

        $translator = static::getContainer()->get('translator');
        assert($translator instanceof TranslatorInterface);

        $coreParametersHelper = static::getContainer()->get('mautic.helper.core_parameters');
        assert($coreParametersHelper instanceof CoreParametersHelper);
        $dateFormat = $coreParametersHelper->get('date_format_dateonly');
        $timeFormat = $coreParametersHelper->get('date_format_timeonly');

        $expectedMessage = $translator->trans('mautic.api.error.entity.locked', [
            '%name%' => $email->getName(),
            '%user%' => $email->getCheckedOutByUser(),
            '%date%' => $email->getCheckedOut()->format($dateFormat),
            '%time%' => $email->getCheckedOut()->format($timeFormat),
        ]);

        $this->assertEquals($expectedMessage, $error['message']);
    }

    private function lockEntityAsAdmin(object $entity): void
    {
        $adminUser = $this->em->getRepository(User::class)->find(1);
        $entity->setCheckedOut(new \DateTime())
            ->setCheckedOutBy($adminUser);

        $this->em->flush();
    }

    private function createAndAuthenticateApiUser(string $username, string $email): void
    {
        $role = $this->em->getRepository(Role::class)->find(1);

        $user = (new User())
            ->setFirstName('API')
            ->setLastName('User')
            ->setEmail($email)
            ->setUsername($username)
            ->setPassword('password')
            ->setRole($role);

        $this->em->persist($user);
        $this->em->flush();

        static::getContainer()->get('mautic.security.user_token_setter')->setUser($user->getId());
    }
}
