<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

final class EmailControllerListingPageTest extends MauticMysqlTestCase
{
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

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-name'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-id'));
        $this->assertCount(0, $crawler->filter('.email-list thead tr th.col-email-category'));
        $this->assertCount(0, $crawler->filter('.email-list thead tr th.col-email-dateModified'));
    }

    public function testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreEmpty(): void
    {
        $this->assertDefaultColumnsAreRendered();
    }

    public function testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreInvalid(): void
    {
        $this->assertDefaultColumnsAreRendered();
    }

    public function testEmailListingAppliesAndPersistsListFiltersFromRequest(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');

        $matchingEmail = $this->createEmail('Email In Segment', $segment);
        $otherEmail    = $this->createEmail('Email Outside Segment');

        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/s/emails',
            [
                'filters' => json_encode(['list:'.$segment->getId()]),
            ]
        );

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('a[href="/s/emails/view/'.$matchingEmail->getId().'"]'));
        $this->assertCount(0, $crawler->filter('a[href="/s/emails/view/'.$otherEmail->getId().'"]'));
        $this->assertSame(
            ['list' => [(string) $segment->getId()]],
            $this->client->getRequest()->getSession()->get('mautic.email.list_filters')
        );

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('a[href="/s/emails/view/'.$matchingEmail->getId().'"]'));
        $this->assertCount(0, $crawler->filter('a[href="/s/emails/view/'.$otherEmail->getId().'"]'));
    }

    public function testEmailListingIgnoresInvalidUpdatedFiltersPayload(): void
    {
        $matchingEmail = $this->createEmail('Email A');
        $otherEmail    = $this->createEmail('Email B');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails', ['filters' => 'not-json']);

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('a[href="/s/emails/view/'.$matchingEmail->getId().'"]'));
        $this->assertCount(1, $crawler->filter('a[href="/s/emails/view/'.$otherEmail->getId().'"]'));
        $this->assertSame([], $this->client->getRequest()->getSession()->get('mautic.email.list_filters'));
    }

    public function testEmailListingAppliesCategoryAndThemeFiltersAndIgnoresUnknownFilterTypes(): void
    {
        $category = $this->createCategory('Email Category', 'email-category');

        $this->createEmail('Email Matching Filters', null, 'template', 'blank')->setCategory($category);

        $this->createEmail('Email Wrong Category', null, 'template', 'blank');

        $this->createEmail('Email Wrong Theme', null, 'template', 'aurora')->setCategory($category);

        $this->em->flush();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/s/emails',
            [
                'filters' => json_encode([
                    'category:'.$category->getId(),
                    'theme:blank',
                    'unknown:value',
                ]),
            ]
        );

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertSame(
            [
                'category' => [(string) $category->getId()],
                'theme'    => ['blank'],
                'unknown'  => ['value'],
            ],
            $this->client->getRequest()->getSession()->get('mautic.email.list_filters')
        );
    }

    public function testEmailListingShowsOnlyOwnedEmailsWithoutViewOtherPermission(): void
    {
        $ownerUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->assertNotNull($ownerUser);
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

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString("var LoginUserName                   = 'sales'", $content);
        $this->assertStringNotContainsString('Other Email', $content);
    }

    public function testEmailListingRedirectsToLastAvailablePageWhenPageIsOutOfBounds(): void
    {
        $this->createEmail();
        $this->client->followRedirects(false);

        $this->client->request(Request::METHOD_GET, '/s/emails/2');

        $this->assertTrue($this->client->getResponse()->isRedirect('/s/emails/1'));
        $this->assertSame(1, $this->client->getRequest()->getSession()->get('mautic.email.page'));
    }

    public function testEmailListingRedirectsToCalculatedLastPageWhenMoreThanOnePageExists(): void
    {
        $this->createEmail('Email A');
        $this->createEmail('Email B');

        $this->client->request(Request::METHOD_GET, '/s/emails');
        $this->client->getRequest()->getSession()->set('mautic.email.limit', 1);
        $this->client->getRequest()->getSession()->save();
        $this->client->followRedirects(false);

        $this->client->request(Request::METHOD_GET, '/s/emails/3');

        $this->assertTrue($this->client->getResponse()->isRedirect('/s/emails/2'));
        $this->assertSame(2, $this->client->getRequest()->getSession()->get('mautic.email.page'));
    }

    private function assertDefaultColumnsAreRendered(): void
    {
        $this->createEmail();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-name'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-category'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-template'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-stats'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-dateAdded'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-dateModified'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-createdByUser'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-id'));
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

    private function createCategory(string $title, string $alias): Category
    {
        $category = new Category();
        $category->setTitle($title);
        $category->setAlias($alias);
        $category->setBundle('global');

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function createEmail(string $name = 'Email A', ?LeadList $segment = null, string $emailType = 'list', ?string $template = null): Email
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
        $roleModel = static::getContainer()->get('mautic.user.model.role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }
}
