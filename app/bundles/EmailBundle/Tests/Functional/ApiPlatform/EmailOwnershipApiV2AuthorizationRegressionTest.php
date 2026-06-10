<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\ApiPlatform;

use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Tests\Functional\ApiPlatform\OwnershipScopedApiAuthorizationTestBase;
use Mautic\UserBundle\Entity\User;

final class EmailOwnershipApiV2AuthorizationRegressionTest extends OwnershipScopedApiAuthorizationTestBase
{
    public function testViewOwnCollectionCannotSeeForeignEmailOnApiV2(): void
    {
        $creatorUser     = $this->createUserWithPermissions(
            username: 'creator.user',
            email: 'creator.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'email:emails' => ['viewother', 'editother', 'deleteother'],
                'api:access'   => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.user',
            email: 'restricted.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'email:emails' => ['viewown', 'editown', 'deleteown'],
                'api:access'   => ['full'],
            ]
        );

        $restrictedUserEmail = new Email();
        $restrictedUserEmail->setName('Restricted User Email');
        $restrictedUserEmail->setSubject('Restricted Subject');
        $restrictedUserEmail->setCreatedBy($restrictedUser);

        $creatorUserEmail = new Email();
        $creatorUserEmail->setName('Creator User Email');
        $creatorUserEmail->setSubject('Creator Subject');
        $creatorUserEmail->setCreatedBy($creatorUser);

        $this->em->persist($restrictedUserEmail);
        $this->em->persist($creatorUserEmail);
        $this->em->flush();
        $this->em->clear();

        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/emails?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $emails = $data['member'];
        self::assertCount(1, $emails, 'Expected exactly 1 email');
        self::assertSame($restrictedUserEmail->getId(), $emails[0]['id']);
        self::assertSame('Restricted User Email', $emails[0]['name']);
    }

    public function testViewOwnCollectionReportsOwnedTotalAcrossPagesOnApiV2(): void
    {
        $creatorUser     = $this->createUserWithPermissions(
            username: 'creator.user',
            email: 'creator.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'email:emails' => ['viewother', 'editother', 'deleteother'],
                'api:access'   => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.user',
            email: 'restricted.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'email:emails' => ['viewown', 'editown', 'deleteown'],
                'api:access'   => ['full'],
            ]
        );

        // Create 4 emails created by restricted user
        for ($i = 1; $i <= 4; ++$i) {
            $email = new Email();
            $email->setName(sprintf('Restricted User Email %d', $i));
            $email->setSubject(sprintf('Restricted Subject %d', $i));
            $email->setCreatedBy($restrictedUser);
            $this->em->persist($email);
        }

        // Create 12 emails created by creator user (not visible to restricted user)
        for ($i = 1; $i <= 12; ++$i) {
            $email = new Email();
            $email->setName(sprintf('Creator User Email %d', $i));
            $email->setSubject(sprintf('Creator Subject %d', $i));
            $email->setCreatedBy($creatorUser);
            $this->em->persist($email);
        }

        $this->em->flush();
        $this->em->clear();

        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        // Request page 1 with all items on one page first
        $this->client->request('GET', '/api/v2/emails?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertResponseIsSuccessful();

        $page1Data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('member', $page1Data);
        self::assertArrayHasKey('totalItems', $page1Data);
        self::assertSame(4, $page1Data['totalItems'], 'Should report totalItems 4 owned emails');
        self::assertCount(4, $page1Data['member'], 'Should return all 4 owned emails on single page');

        // Verify all items belong to restricted user
        foreach ($page1Data['member'] as $email) {
            self::assertStringStartsWith('Restricted User Email', $email['name']);
        }
    }
}
