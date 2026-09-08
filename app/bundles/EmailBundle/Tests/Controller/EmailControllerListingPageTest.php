<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpFoundation\Request;

final class EmailControllerListingPageTest extends MauticMysqlTestCase
{
    private const EMAIL_INDEX_PATH            = '/s/emails';

    private const EMAIL_VIEW_SELECTOR_PREFIX  = 'a[href="/s/emails/view/';

    private const DEFAULT_EMAIL_NAME          = 'Email A';

    protected function setUp(): void
    {
        $this->configParams['email_columns'] = match ($this->name()) {
            'testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreEmpty'   => [],
            'testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreInvalid' => ['does_not_exist'],
            default                                                                    => ['name', 'id'],
        };

        parent::setUp();
    }

    public function testEmailListingColumnsCanBeConfigured(): void
    {
        $this->createEmail();

        $crawler = $this->client->request(Request::METHOD_GET, self::EMAIL_INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-name'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-id'));
        $this->assertCount(0, $crawler->filter('.email-list thead tr th.col-email-category'));
        $this->assertCount(0, $crawler->filter('.email-list thead tr th.col-email-dateModified'));
    }

    public function testEmailListingAppliesAndPersistsListFiltersFromRequest(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');

        $matchingEmail = $this->createEmail('Email In Segment', $segment);
        $otherEmail    = $this->createEmail('Email Outside Segment');

        $crawler = $this->client->request(
            Request::METHOD_GET,
            self::EMAIL_INDEX_PATH,
            [
                'filters' => json_encode(['list:'.$segment->getId()]),
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter(self::EMAIL_VIEW_SELECTOR_PREFIX.$matchingEmail->getId().'"]'));
        $this->assertCount(0, $crawler->filter(self::EMAIL_VIEW_SELECTOR_PREFIX.$otherEmail->getId().'"]'));
        $this->assertSame(
            ['list' => [(string) $segment->getId()]],
            $this->client->getRequest()->getSession()->get('mautic.email.list_filters')
        );

        $crawler = $this->client->request(Request::METHOD_GET, self::EMAIL_INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter(self::EMAIL_VIEW_SELECTOR_PREFIX.$matchingEmail->getId().'"]'));
        $this->assertCount(0, $crawler->filter(self::EMAIL_VIEW_SELECTOR_PREFIX.$otherEmail->getId().'"]'));
    }

    public function testEmailListingShowsOnlyOwnedEmailsWithoutViewOtherPermission(): void
    {
        $ownerUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->assertInstanceOf(User::class, $ownerUser);
        $this->setPermission($ownerUser->getRole(), ['email:emails' => ['viewown']]);

        $ownedEmail = $this->createEmail('Owned Email', null, 'template', 'blank');
        $ownedEmail->setCreatedBy($ownerUser->getId());
        $ownedEmail->setCreatedByUser($ownerUser->getName());

        $otherEmail = $this->createEmail('Other Email', null, 'template', 'blank');
        $otherEmail->setCreatedByUser('Admin User');

        $this->em->flush();
        $this->loginUser($ownerUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $ownerUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_GET, self::EMAIL_INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString("var LoginUserName                   = 'sales'", (string) $content);
        $this->assertStringNotContainsString('Other Email', (string) $content);
    }

    private function createSegment(string $name, string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setAlias($alias);
        $segment->setPublicName($name);

        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createEmail(string $name = self::DEFAULT_EMAIL_NAME, ?LeadList $segment = null, string $emailType = 'list', ?string $template = null): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name.' Subject');
        $email->setEmailType($emailType);
        $email->setTemplate($template);

        if (null !== $segment) {
            $email->addList($segment);
        }

        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function setPermission(Role $role, array $permissions): void
    {
        $roleModel = self::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }
}
