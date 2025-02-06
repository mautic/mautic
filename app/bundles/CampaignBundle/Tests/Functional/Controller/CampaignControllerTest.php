<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    use UserEntityTrait;

    private Lead $contactOne;
    private Lead $contactTwo;
    private Lead $contactThree;
    private Campaign $campaign;

    /**
     * @throws NotSupported
     * @throws ORMException
     * @throws MappingException
     */
    public function testContactsGridForValidPermissions(): void
    {
        $nonAdminUser = $this->setupCampaignData(38);

        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/campaigns/view/'.$this->campaign->getId().'/contact/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($this->contactOne->getName(), $content);
        $this->assertStringContainsString($this->contactTwo->getName(), $content);
        $this->assertStringContainsString($this->contactThree->getName(), $content);
    }

    /**
     * @throws OptimisticLockException
     * @throws MappingException
     * @throws ORMException
     * @throws NotSupported
     */
    public function testContactsGridWhenIncompleteValidPermissions(): void
    {
        $nonAdminUser = $this->setupCampaignData();

        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/campaigns/view/'.$this->campaign->getId().'/contact/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('No Contacts Found', $content, $content);
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
        $nonAdminUser = $this->createUserWithPermission([
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
        $this->contactOne   = $this->createLead('John', '', '', $adminUser);
        $this->contactTwo   = $this->createLead('Alex', '', '', $adminUser);
        $this->contactThree = $this->createLead('Gemini', '', '', $nonAdminUser);

        // Create Segment
        $segment = $this->createSegment('seg1', []);

        // Add contacts to segment
        $this->createListLead($segment, $this->contactOne);
        $this->createListLead($segment, $this->contactTwo);
        $this->createListLead($segment, $this->contactThree);

        $this->campaign = $this->createCampaign('Campaign');
        $this->campaign->addList($segment);

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
