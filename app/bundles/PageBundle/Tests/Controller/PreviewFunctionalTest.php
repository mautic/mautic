<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewFunctionalTest extends MauticMysqlTestCase
{
    public function testPreviewPageWithContact(): void
    {
        $user           = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $lead           = $this->createLead();
        $dynamicContent = $this->createDynamicContent($lead);
        $defaultContent = 'Default web content';
        // Create non public landing page.
        $page           = $this->createPage($dynamicContent, $defaultContent, true, false);

        $this->em->flush();
        $this->em->clear();

        $url = "/page/preview/{$page->getId()}";

        // Anonymous visitor is not allowed to access preview if not public
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->client->request(Request::METHOD_GET, $url);
        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->loginUser($user);

        // Admin user is allowed to access preview
        $this->assertPageContent($url, $defaultContent);

        // Check DWC replacement for the given lead
        $this->assertPageContent("{$url}?contactId={$lead->getId()}", $dynamicContent->getContent());

        // Check there is no DWC replacement for a non-existent lead
        $this->assertPageContent("{$url}?contactId=987", $defaultContent);

        $this->logoutUser();

        // Anonymous visitor is not allowed to access preview
        $this->client->request(Request::METHOD_GET, $url);
        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPreviewPageUrlIsValid(): void
    {
        $page = $this->createPage();

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check for correct preview URL.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages/view/'.$pageId);
        self::assertStringContainsString('/page/preview/'.$pageId, $crawler->filter('#content_preview_url')->attr('value'));
    }

    public function testPreviewPagePublicToggle(): void
    {
        $page = $this->createPage();

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check for public preview ON.
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/pages/view/'.$pageId);
        $toggleElem = $crawler->filter('i.ri-toggle-fill');
        self::assertEquals(1, $toggleElem->count());

        // Toggle public preview.
        $parameters = [
            'action'       => 'togglePublishStatus',
            'model'        => 'page',
            'id'           => $pageId,
            'customToggle' => 'publicPreview',
        ];
        $this->client->request(Request::METHOD_POST, '/s/ajax', $parameters);

        // Check for public preview OFF.
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/pages/view/'.$pageId);
        $toggleElem = $crawler->filter('i.ri-toggle-line');
        self::assertEquals(1, $toggleElem->count());

        // Create landing page with public preview OFF.
        $page = $this->createPage(null, '', true, false);

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check for public preview OFF.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages/view/'.$pageId);
        self::assertEquals(1, $crawler->filter('i.ri-toggle-line')->count());

        // Toggle public preview.
        $parameters['id'] = $pageId;
        $this->client->request(Request::METHOD_POST, '/s/ajax', $parameters);

        // Check for public preview ON.
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/pages/view/'.$pageId);
        $toggleElem = $crawler->filter('i.ri-toggle-fill');
        self::assertEquals(1, $toggleElem->count());
    }

    public function testPreviewPageWithPublishAndPublicOptions(): void
    {
        $page = $this->createPage();

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check for public preview ON.
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/'.$pageId);
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertEquals('Hello', $crawler->text());

        // Create landing page with public preview OFF.
        $page = $this->createPage(null, '', true, false);

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check public preview without login.

        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/'.$pageId);
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString(
            'Unauthorized access to requested URL: /page/preview/'.$pageId,
            $crawler->text()
        );

        // Create page with publish OFF.
        $page = $this->createPage(null, '', false);

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check for public preview ON.
        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/'.$pageId);
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString(
            'Unauthorized access to requested URL: /page/preview/'.$pageId,
            $crawler->text()
        );

        // Create landing page with publish and public preview OFF.
        $page = $this->createPage(null, '', false, false);

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check for public preview ON.
        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/'.$pageId);
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString(
            'Unauthorized access to requested URL: /page/preview/'.$pageId,
            $crawler->text()
        );
    }

    public function testPreviewPageNotFound(): void
    {
        // Check for non existing landing page preview.
        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/20000');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('404 Not Found', $crawler->text());
    }

    public function testPreviewPageAccess(): void
    {
        // Create non published, non public landing page.
        $page = $this->createPage(null, '', false, false);

        $this->em->flush();
        $this->em->clear();

        $pageId = $page->getId();

        // Check public preview with login.
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);
        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/'.$pageId);
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertEquals('Hello', $crawler->text());

        // Check public preview without login.
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $crawler = $this->client->request(Request::METHOD_GET, '/page/preview/'.$pageId);
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString(
            'Unauthorized access to requested URL: /page/preview/'.$pageId,
            $crawler->text()
        );

        // Check public preview access without permissions
        $this->loginUser($user);
        $security = $this->createMock(CorePermissions::class);
        $security->method('isAnonymous')->willReturn(false);
        $security->method('hasEntityAccess')->with(
            'page:pages:viewown',
            'page:pages:viewother',
            $page->getCreatedBy()
        )->willReturn(false);
        $this->getContainer()->set('mautic.security', $security);
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString(
            'Unauthorized access to requested URL: /page/preview/'.$pageId,
            $crawler->text()
        );
    }

    private function assertPageContent(string $url, string $expectedContent): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        self::assertSame($expectedContent, $crawler->text());
    }

    private function createPage(
        ?DynamicContent $dynamicContent = null,
        string $defaultContent = '',
        bool $isPublished = true,
        bool $publicPreview = true,
    ): Page {
        if (null === $dynamicContent) {
            $customHtml = '<html lang="en"><body>Hello</body></html>';
        } else {
            $customHtml = sprintf('<div data-slot="dwc" data-param-slot-name="%s"><span>%s</span></div>', $dynamicContent->getSlotName(), $defaultContent);
        }

        $page = new Page();
        $page->setIsPublished($isPublished);
        $page->setDateAdded(new \DateTime());
        $page->setTitle('Preview settings test - main page');
        $page->setAlias('page-main');
        $page->setTemplate('Blank');
        $page->setCustomHtml($customHtml);
        $page->setPublicPreview($publicPreview);
        $this->em->persist($page);

        return $page;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('test@domain.tld');
        $this->em->persist($lead);

        return $lead;
    }

    private function createDynamicContent(Lead $lead): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('Test DWC');
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setContent('DWC content');
        $dynamicContent->setSlotName('test');
        $dynamicContent->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => $lead->getEmail(),
                'display'  => null,
                'operator' => '=',
            ],
        ]);
        $this->em->persist($dynamicContent);

        return $dynamicContent;
    }
}
