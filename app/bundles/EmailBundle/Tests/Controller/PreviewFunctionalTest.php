<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class PreviewFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const PREHEADER_TEXT = 'Preheader text';

    protected $useCleanupRollback = false;

    public function testPreviewPage(): void
    {
        $lead  = $this->createLead('John', 'Doe', 'test@domain.tld');
        $email = $this->createEmail();
        $this->em->flush();

        $url                    = "/email/preview/{$email->getId()}";
        $urlWithContact         = "{$url}?contactId={$lead->getId()}";
        $contentNoContactInfo   = 'Contact emails is [Email]';
        $contentWithContactInfo = sprintf('Contact emails is %s', $lead->getEmail());

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo, self::PREHEADER_TEXT);

        $this->logoutUser();

        // Anonymous visitor
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo, self::PREHEADER_TEXT);
    }

    public function testPreviewPageWithTemplateAndNoSavedHtml(): void
    {
        $email = $this->createEmail();
        $email->setCustomHtml('');
        $email->setContent([]);
        $this->em->flush();

        $this->assertPageContent("/email/preview/{$email->getId()}", 'Hello World!');
    }

    private function assertPageContent(string $url, string ...$expectedContents): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        self::assertResponseIsSuccessful();
        foreach ($expectedContents as $expectedContent) {
            $this->assertStringContainsString($expectedContent, $crawler->text());
        }
    }

    private function createEmail(bool $publicPreview = true): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setPublicPreview($publicPreview);
        $email->setCustomHtml('<html><body>Contact emails is {contactfield=email}</body></html>');
        $email->setPreheaderText(self::PREHEADER_TEXT);
        $this->em->persist($email);

        return $email;
    }

    public function testPreviewEmailWithCorrectDCVariationFilterSegmentMembership(): void
    {
        $segment1 = $this->createSegment('Segment 1');
        $segment2 = $this->createSegment('Segment 2');
        $lead     = $this->createLead('John', 'Doe', 'test@domain.tld');
        $this->addLeadToSegment($lead, $segment1);
        $email = $this->createEmail();

        $email->setDynamicContent([
            [
                'tokenName' => 'Dynamic Content 1',
                'content'   => '<p>Default Dynamic Content</p>',
                'filters'   => [
                    [
                        'content' => '<p>Variation 1</p>',
                        'filters' => [
                            [
                                'glue'   => 'and',
                                'field'  => 'leadlist',
                                'object' => 'lead',
                                'type'   => 'leadlist',
                                'filter' => [
                                    $segment1->getId(),
                                    $segment2->getId(),
                                ],
                                'display'  => 'Segment Membership',
                                'operator' => 'in',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $email->setCustomHtml('<html><body>{dynamiccontent="Dynamic Content 1"}</body></html>');
        $this->em->persist($email);
        $this->em->flush();

        $url                    = "/email/preview/{$email->getId()}";
        $urlWithContact         = "{$url}?contactId={$lead->getId()}";
        $contentNoContactInfo   = 'Default Dynamic Content';
        $contentWithContactInfo = 'Variation 1';

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo, self::PREHEADER_TEXT);

        $this->logoutUser();

        // Anonymous visitor
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo, self::PREHEADER_TEXT);
    }

    public function testPreviewEmailForDynamicContentVariantsWithCustomField(): void
    {
        // Create custom field
        $this->client->request(
            'POST',
            '/api/fields/contact/new',
            [
                'label'      => 'bool',
                'type'       => 'boolean',
                'properties' => [
                    'no'  => 'No',
                    'yes' => 'Yes',
                ],
            ]
        );
        self::assertResponseStatusCodeSame(201);
        $this->assertJson($this->client->getResponse()->getContent());

        // Create some contacts
        $this->client->request(
            'POST',
            '/api/contacts/batch/new',
            [
                [
                    'firstname' => 'John',
                    'lastname'  => 'A',
                    'email'     => 'john.a@email.com',
                    'bool'      => true,
                ],
                [
                    'firstname' => 'John',
                    'lastname'  => 'B',
                    'email'     => 'john.b@email.com',
                    'bool'      => false,
                ],
                [
                    'firstname' => 'John',
                    'lastname'  => 'C',
                    'email'     => 'john.c@email.com',
                    'bool'      => null,
                ],
            ]
        );
        self::assertResponseStatusCodeSame(201, $this->client->getResponse()->getContent());
        $contacts = json_decode($this->client->getResponse()->getContent(), true);

        // Create email with dynamic content variant
        $email          = $this->createEmail();
        $dynamicContent = [
            [
                'tokenName' => 'Dynamic Content 1',
                'content'   => '<p>Default Dynamic Content</p>',
                'filters'   => [
                    [
                        'content' => null,
                        'filters' => [],
                    ],
                ],
            ],
            [
                'tokenName' => 'Dynamic Content 2',
                'content'   => '<p>Default Dynamic Content</p>',
                'filters'   => [
                    [
                        'content' => '<p>Variant 1 Dynamic Content</p>',
                        'filters' => [
                            [
                                'glue'     => 'and',
                                'field'    => 'bool',
                                'object'   => 'lead',
                                'type'     => 'boolean',
                                'filter'   => '1',
                                'display'  => null,
                                'operator' => '=',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $email->setCustomHtml('<html><body><div>{dynamiccontent="Dynamic Content 2"}</div></body></html>');
        $email->setDynamicContent($dynamicContent);
        $this->em->flush();

        $url            = "/email/preview/{$email->getId()}";
        $defaultContent = 'Default Dynamic Content';
        $variantContent = 'Variant 1 Dynamic Content';

        // Admin user with contact preview - show variant content - true filter matches
        $urlWithContact1 = "{$url}?contactId={$contacts['contacts'][0]['id']}";
        $this->assertPageContent($urlWithContact1, $variantContent);

        // Admin user with contact preview - show variant content - false filter doesn't matches
        $urlWithContact2 = "{$url}?contactId={$contacts['contacts'][1]['id']}";
        $this->assertPageContent($urlWithContact2, $defaultContent);

        // Admin user with contact preview - show variant content - null filter doesn't matches
        $urlWithContact3 = "{$url}?contactId={$contacts['contacts'][2]['id']}";
        $this->assertPageContent($urlWithContact3, $defaultContent);

        $this->logoutUser();

        // Non admin user - show default content
        $this->assertPageContent($url, $defaultContent);

        // Non admin user with contact preview - show default content
        $urlWithContact1 = "{$url}?contactId={$contacts['contacts'][0]['id']}";
        $this->assertPageContent($urlWithContact1, $defaultContent);
    }

    public function testPreviewEmailWithInvalidIdThrows404Error(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/email/preview/5009');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringContainsString('404 Not Found - Requested URL not found: /email/preview/5009', $crawler->text());
    }

    private function createSegment(string $name = 'Segment 1'): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(strtolower($name));
        $segment->isPublished(true);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function addLeadToSegment(Lead $lead, LeadList $segment): ListLead
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);
        $this->em->flush();

        return $listLead;
    }

    public function testPreviewEmailForContactWithPrimaryCompany(): void
    {
        $company = $this->createCompany('Mautic', 'hello@mautic.org');
        $company->setCity('Pune');
        $company->setCountry('India');

        $this->em->persist($company);

        $lead    = $this->createLead('John', 'Doe', 'test@domain.tld');
        $lead->setCompany($company->getName());
        $this->em->persist($lead);

        $this->createPrimaryCompanyForLead($lead, $company);

        $email = $this->createEmail();
        $email->setCustomHtml('<html><body>Contact emails is {contactfield=email}. Company Name: {contactfield=companyname} and Company City: {contactfield=companycity}</body></html>');

        $this->em->flush();

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $url                    = "/email/preview/{$email->getId()}";
        $urlWithContact         = "{$url}?contactId={$lead->getId()}";
        $contentNoContactInfo   = 'Contact emails is [Email]. Company Name: [Company Name] and Company City: [City]';
        $contentWithContactInfo = sprintf('Contact emails is %s. Company Name: %s and Company City: %s', $lead->getEmail(), $company->getName(), $company->getCity());

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo);

        $this->logoutUser();

        // Anonymous visitor
        $this->assertPageContent($url, $contentNoContactInfo);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo);
    }

    public function testPreviewDownloadPage(): void
    {
        $email = $this->createEmail(false);
        $this->em->flush();

        $url = sprintf('/email/download/preview/%s', $email->getId());

        // Admin user
        $this->assertPageContent($url, 'Download HTML');

        $this->logoutUser();

        // Anonymous visitor
        $response = $this->getUrlResponse($url);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPreviewDownloadForHtml(): void
    {
        $lead  = $this->createLead('John');
        $email        = $this->createEmail();
        $emailName    = $email->getName();
        $fileName     = 'John-'.$emailName;
        $this->em->flush();

        // Test HTML file download without any contact.
        $response = $this->getUrlResponse(sprintf('/email/download/preview/%s/real/html', $email->getId()));
        $this->assertStringContainsString($emailName.'.html', (string) $response->headers->get('content-disposition'));

        // Test HTML file download with contact.
        $response = $this->getUrlResponse(sprintf('/email/download/preview/%s/real/html?contactId=%s', $email->getId(), $lead->getId()));
        $this->assertStringContainsString($fileName.'.html', (string) $response->headers->get('content-disposition'));
    }

    public function testPreviewEmailForUnauthorizedContact(): void
    {
        $lead  = $this->createLead('Test');
        // Create non-admin role
        $permission = ['email:emails' => ['full'], 'lead:leads' => ['viewown', 'create']];
        $role       = $this->createRole(false, $permission);
        // Create permissions for the role
        $this->createPermission('lead:leads', $role, 34);
        $this->createPermission('email:emails', $role, 1024);
        // Create non-admin user
        $user = $this->createUser($role);
        $this->em->flush();

        // Login newly created non-admin user
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $email = $this->createEmail();
        $this->em->flush();
        $errorMsg = 'You do not have access to this contact.';

        $crawler = $this->client->request(Request::METHOD_GET, "/email/download/preview/{$email->getId()}?contactId={$lead->getId()}");
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($errorMsg, (string) $content);
        $this->assertStringContainsString('Contact emails is [Email]', $this->getIframeContent($crawler));

        $lead  = $this->createLead('Test');
        $lead->setOwner($user);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/email/download/preview/{$email->getId()}?contactId={$lead->getId()}");
        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString($errorMsg, (string) $content);
        $this->assertStringContainsString(sprintf('Contact emails is %s', $lead->getEmail()), $this->getIframeContent($crawler));
    }

    private function getUrlResponse(string $url): Response
    {
        $this->client->request(Request::METHOD_GET, $url);

        return $this->client->getResponse();
    }

    private function getIframeContent(Crawler $crawler): string
    {
        $iframe    = $crawler->filterXPath('//iframe')->eq(0);
        $iframeSrc = $iframe->attr('src');
        $this->client->request('GET', $iframeSrc);

        return $this->client->getResponse()->getContent();
    }

    /**
     * @param array<mixed> $permission
     */
    private function createRole(bool $isAdmin = false, array $permission = []): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);
        $role->setRawPermissions($permission);
        $this->em->persist($role);

        return $role;
    }

    private function createPermission(string $rawPermission, Role $role, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $user->setRole($role);
        $this->em->persist($user);

        return $user;
    }
}
