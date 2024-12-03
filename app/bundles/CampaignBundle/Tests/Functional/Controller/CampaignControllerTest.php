<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class CampaignControllerTest extends MauticMysqlTestCase
{
    private Lead $contactOne;
    private Lead $contactTwo;
    private Lead $contactThree;
    private Campaign $campaign;

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    public function testContactsGridForValidPermissions(): void
    {
        $nonAdminUser = $this->setupCampaignData(38);

        $this->loginOtherUser($nonAdminUser->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/campaigns/view/'.$this->campaign->getId().'/contact/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($this->contactOne->getName(), $content);
        $this->assertStringContainsString($this->contactTwo->getName(), $content);
        $this->assertStringContainsString($this->contactThree->getName(), $content);
    }

    public function testContactsGridWhenIncompleteValidPermissions(): void
    {
        $nonAdminUser = $this->setupCampaignData();

        $this->loginOtherUser($nonAdminUser->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/campaigns/view/'.$this->campaign->getId().'/contact/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('No Contacts Found', $content, $content);
    }

    private function loginOtherUser(string $name): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($name);
        $this->client->setServerParameter('PHP_AUTH_USER', $name);
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
    }

    /**
     * @throws ORMException
     */
    private function createSegmentMember(LeadList $segment, Lead $lead): void
    {
        $segmentMember = new ListLead();
        $segmentMember->setLead($lead);
        $segmentMember->setList($segment);
        $segmentMember->setDateAdded(new \DateTime());
        $this->em->persist($segmentMember);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     */
    private function createCampaign(LeadList $segment): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('c1');
        $campaign->setIsPublished(true);
        $campaign->addList($segment);
        $this->em->persist($campaign);

        return $campaign;
    }

    /**
     * @param array<mixed> $property
     *
     * @throws ORMException
     */
    protected function createEvent(
        string $name,
        Campaign $campaign,
        string $type,
        string $eventType,
        array $property = null,
    ): Event {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType($type);
        $event->setEventType($eventType);
        $event->setTriggerInterval(1);
        $event->setProperties($property);
        $event->setTriggerMode('immediate');
        $this->em->persist($event);

        return $event;
    }

    /**
     * @param array<string, mixed> $userDetails
     */
    private function createUser(array $userDetails): User
    {
        $role = new Role();
        $role->setName($userDetails['role']['name']);
        $role->setIsAdmin(false);

        $this->em->persist($role);

        foreach ($userDetails['role']['permissions'] as $permission => $bitwise) {
            $this->createPermission($role, $permission, $bitwise);
        }

        $user = new User();
        $user->setEmail($userDetails['email']);
        $user->setUsername($userDetails['user-name']);
        $user->setFirstName($userDetails['first-name']);
        $user->setLastName($userDetails['last-name']);
        $user->setRole($role);

        /** @var PasswordHasherInterface $encoder */
        $encoder = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        $user->setPassword($encoder->hash('mautic'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createPermission(Role $role, string $rawPermission, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }

    private function createSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(str_shuffle('abcdefghijklmnopqrstuvwxyz'));

        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createLead(string $name, User $user): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($name);
        $lead->setCreatedByUser($user);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * @throws ORMException
     * @throws MappingException
     * @throws OptimisticLockException
     * @throws NotSupported
     */
    private function setupCampaignData(int $bitwise = 2): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);
        $adminUser      = $userRepository->findOneBy(['username' => 'admin']);

        // create users
        $nonAdminUser = $this->createUser([
            'user-name'  => 'non-admin',
            'email'      => 'non-admin@mautic-test.com',
            'first-name' => 'non-admin',
            'last-name'  => 'non-admin',
            'role'       => [
                'name'        => 'perm_non_admin',
                'permissions' => [
                    'lead:leads'         => $bitwise,
                    'campaign:campaigns' => 2,
                ],
            ],
        ]);

        // create contacts
        $this->contactOne   = $this->createLead('John', $adminUser);
        $this->contactTwo   = $this->createLead('Alex', $adminUser);
        $this->contactThree = $this->createLead('Gemini', $nonAdminUser);

        // Create Segment
        $segment = $this->createSegment('seg1', $nonAdminUser);

        // Add contacts to segment
        $this->createSegmentMember($segment, $this->contactOne);
        $this->createSegmentMember($segment, $this->contactTwo);
        $this->createSegmentMember($segment, $this->contactThree);

        $this->campaign = $this->createCampaign($segment);

        $this->createEvent('Add 10 points', $this->campaign,
            'lead.changepoints',
            'action',
            ['points' => 10]
        );

        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $this->campaign->getId(), '-vv']);

        return $nonAdminUser;
    }
}
