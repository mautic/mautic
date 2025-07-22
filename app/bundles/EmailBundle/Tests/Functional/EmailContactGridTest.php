<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailContactGridTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    use UserEntityTrait;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function testEmailContactsGridWithValidPermissions(): void
    {
        list($email, $contactOne, $contactTwo) = $this->setupData();

        // create users
        $nonAdminUser = $this->createUserWithPermission([
            'user-name'  => 'non-admin',
            'email'      => 'non-admin@mautic-test.com',
            'first-name' => 'non-admin',
            'last-name'  => 'non-admin',
            'role'       => [
                'name'        => 'perm_non_admin',
                'permissions' => [
                    'lead:leads'   => 6,
                    'email:emails' => 6,
                ],
            ],
        ]);

        $this->em->flush();
        $this->em->clear();

        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/emails/view/'.$email->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString($contactOne->getName(), $content);
        $this->assertStringContainsString($contactTwo->getName(), $content);
    }

    /**
     * @throws OptimisticLockException
     * @throws MappingException
     * @throws ORMException
     */
    public function testEmailContactsGridWithIncompletePermissions(): void
    {
        /** @var Email $email */
        list($email, $contactOne, $contactTwo) = $this->setupData();

        // create users
        $nonAdminUser = $this->createUserWithPermission([
            'user-name'  => 'non-admin',
            'email'      => 'non-admin@mautic-test.com',
            'first-name' => 'non-admin',
            'last-name'  => 'non-admin',
            'role'       => [
                'name'        => 'perm_non_admin',
                'permissions' => [
                    'lead:leads'   => 2,
                    'email:emails' => 6,
                ],
            ],
        ]);

        $email->setCreatedBy($nonAdminUser);
        $this->em->persist($email);

        $this->em->flush();
        $this->em->clear();

        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/emails/view/'.$email->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('No Contacts Found', $content, $content);
    }

    /**
     * @throws ORMException
     */
    private function createStats(Email $email, Lead $contactOne): void
    {
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contactOne);
        $emailStat->setDateSent(new \DateTime());
        $emailStat->setEmailAddress($contactOne->getEmail());

        $this->em->persist($emailStat);
    }

    /**
     * @param array<string, mixed> $userDetails
     */
    private function createUserWithPermission(array $userDetails): User
    {
        $role = $this->createRole($userDetails['role']['name']);

        foreach ($userDetails['role']['permissions'] as $permission => $bitwise) {
            $this->createPermission($role, $permission, $bitwise);
        }

        return $this->createUser($userDetails['email'], $userDetails['user-name'], $userDetails['first-name'], $userDetails['last-name'], $role);
    }

    /**
     * @return array<mixed>
     *
     * @throws ORMException
     */
    private function setupData(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);
        $adminUser      = $userRepository->findOneBy(['username' => 'admin']);

        $segment = $this->createSegment('SegmentOne', []);

        $email = $this->createEmail('Hello');
        $email->setEmailType('list');
        $email->addList($segment);
        $email->setCustomHtml('<h1>Email content created by an API test</h1>{custom-token}<br>{signature}');
        $email->setIsPublished(true);

        $this->em->persist($email);

        // Create Contact
        $contactOne = $this->createLead('John', '', 'john@contact.email', $adminUser);
        $contactTwo = $this->createLead('Alex', '', 'alex@contact.email', $adminUser);

        // Create stats
        $this->createStats($email, $contactOne);
        $this->createStats($email, $contactTwo);

        return [$email, $contactOne, $contactTwo];
    }
}
