<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class CompanySegmentControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useCleanupRollback = false;
        $this->setUpSymfony($this->configParams);
        $this->loginAdminUser();
    }

    public function testIndexActionReturnsSuccessfully(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/company-segments');
        $this->assertResponseIsSuccessful('Company segments index should return 200.');
    }

    public function testIndexActionDisplaysCompanySegments(): void
    {
        $segment1 = $this->createCompanySegment('First Segment', 'first');
        $segment2 = $this->createCompanySegment('Second Segment', 'second');

        $this->em->persist($segment1);
        $this->em->persist($segment2);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/company-segments');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful('Company segments index should return 200.');
        $this->assertStringContainsString('First Segment', $response->getContent());
        $this->assertStringContainsString('Second Segment', $response->getContent());
    }

    public function testIndexActionWithFiltering(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/company-segments?search=is:published&tmpl=list');
        $this->assertResponseIsSuccessful('Filtering should return 200.');
    }

    /**
     * Test that index shows correct metadata (dates, creator).
     */
    public function testIndexActionDisplaysMetadata(): void
    {
        $segment = $this->createCompanySegment('Metadata Test');
        $segment->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $segment->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $segment->setCreatedByUser('Test User');

        $this->em->persist($segment);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/company-segments');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful('Company segments index should return 200.');
        $this->assertStringContainsString('February 7, 2020', $response->getContent());
        $this->assertStringContainsString('March 21, 2020', $response->getContent());
        $this->assertStringContainsString('Test User', $response->getContent());
    }

    private function createCompanySegment(string $name, ?string $aliasSuffix = null): CompanySegment
    {
        $alias = $aliasSuffix ?? strtolower(str_replace(' ', '-', $name));

        $segment = new CompanySegment();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);
        $segment->setFilters([]);
        $segment->setIsPublished(true);

        return $segment;
    }

    public function testNewActionDisplaysForm(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/company-segments/new');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful('New company segment form should return 200.');
        $this->assertStringContainsString('New company segment', $response->getContent());
        $this->assertStringContainsString('company_segments[name]', $response->getContent());
    }

    public function testNewActionCreatesSegment(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/company-segments/new');
        $form    = $crawler->filter('[name="company_segments"]')->form([
            'company_segments' => [
                'name'        => 'Test New Segment',
                'alias'       => 'test-new-segment',
                'description' => 'Test description',
                'isPublished' => '1',
            ],
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful('Form submission should succeed.');

        // Should display the view page with segment name in header
        $this->assertCount(1, $crawler->filter('.page-header'));
        $this->assertStringContainsString('Test New Segment', $crawler->filter('.page-header')->text());

        // Verify segment was created in database
        $this->em->clear();
        $segment = $this->em->getRepository(CompanySegment::class)->findOneBy(['alias' => 'test-new-segment']);
        $this->assertInstanceOf(CompanySegment::class, $segment);
        $this->assertSame('Test New Segment', $segment->getName());
        $this->assertSame('Test description', $segment->getDescription());
        $this->assertTrue($segment->isPublished());
    }

    public function testEditActionDisplaysForm(): void
    {
        $segment = $this->createCompanySegment('Edit Test Segment', 'edit-test');
        $this->em->persist($segment);
        $this->em->flush();

        $segmentId = $segment->getId();
        \assert(null !== $segmentId);

        $this->client->request(Request::METHOD_GET, '/s/company-segments/edit/'.$segmentId);
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful('Edit company segment form should return 200.');
        $this->assertStringContainsString('Edit company segment - Edit Test Segment', $response->getContent());
        $this->assertStringContainsString('value="Edit Test Segment"', $response->getContent());
    }

    public function testEditActionUpdatesSegment(): void
    {
        $segment = $this->createCompanySegment('Original Name', 'original-alias');
        $this->em->persist($segment);
        $this->em->flush();

        $segmentId = $segment->getId();
        \assert(null !== $segmentId);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/company-segments/edit/'.$segmentId);
        $form    = $crawler->filter('[name="company_segments"]')->form([
            'company_segments' => [
                'name'        => 'Updated Name',
                'description' => 'Updated description',
            ],
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful('Form submission should succeed.');

        // Should display the view page with updated name in header
        $this->assertCount(1, $crawler->filter('.page-header'));
        $this->assertStringContainsString('Updated Name', $crawler->filter('.page-header')->text());

        // Verify segment was updated in database
        $this->em->clear();
        $updatedSegment = $this->em->getRepository(CompanySegment::class)->find($segmentId);
        $this->assertInstanceOf(CompanySegment::class, $updatedSegment);
        $this->assertSame('Updated Name', $updatedSegment->getName());
        $this->assertSame('Updated description', $updatedSegment->getDescription());
    }

    public function testViewActionDisplaysSegmentDetails(): void
    {
        $segment = $this->createCompanySegment('View Test Segment', 'view-test');
        $segment->setDescription('This is a test segment for viewing');
        $this->em->persist($segment);
        $this->em->flush();

        $segmentId = $segment->getId();
        \assert(null !== $segmentId);

        $this->client->request(Request::METHOD_GET, '/s/company-segments/view/'.$segmentId);
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful('View company segment should return 200.');
        $this->assertStringContainsString('View Test Segment', $response->getContent());
        $this->assertStringContainsString('This is a test segment for viewing', $response->getContent());
    }

    public function testEditActionReturnsErrorForNonExistentSegment(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/company-segments/edit/99999');

        // Should redirect or show index page (Mautic's postActionRedirect can do either)
        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->isSuccessful(),
            'Non-existent segment should redirect or show index page'
        );

        // Follow redirect if there is one
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        // Verify we ended up on the index/list page: /s/company-segments or /s/company-segments/{page}
        // NOT on: /s/company-segments/edit/99999
        $currentUrl = $this->client->getRequest()->getUri();
        $this->assertMatchesRegularExpression(
            '#/s/company-segments(/\d+)?$#',
            $currentUrl,
            'Should be on company segments index page (/s/company-segments or /s/company-segments/{page})'
        );
    }

    public function testViewActionReturnsErrorForNonExistentSegment(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/company-segments/view/99999');

        // Should redirect or show index page (Mautic's postActionRedirect can do either)
        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->isSuccessful(),
            'Non-existent segment should redirect or show index page'
        );

        // Follow redirect if there is one
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        // Verify we ended up on the index/list page: /s/company-segments or /s/company-segments/{page}
        // NOT on: /s/company-segments/view/99999 or /s/company-segments/edit/99999 or /s/company-segments/new
        $currentUrl = $this->client->getRequest()->getUri();
        $this->assertMatchesRegularExpression(
            '#/s/company-segments(/\d+)?$#',
            $currentUrl,
            'Should be on company segments index page (/s/company-segments or /s/company-segments/{page})'
        );
    }

    private function loginAdminUser(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        \assert($user instanceof User);
        $this->loginUser($user);
    }
}
