<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CompanySegmentControllerTest extends MauticMysqlTestCase
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

    public function testDeleteActionRemovesSegment(): void
    {
        $segment = $this->createCompanySegment('Delete Test Segment', 'delete-test');
        $this->em->persist($segment);
        $this->em->flush();

        $segmentId = $segment->getId();
        \assert(null !== $segmentId);

        // Send POST request to delete
        $this->client->request(Request::METHOD_POST, '/s/company-segments/delete/'.$segmentId);

        // Should redirect to index
        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->isSuccessful(),
            'Delete action should redirect or show success page'
        );

        // Verify segment was deleted from database
        $this->em->clear();
        $deletedSegment = $this->em->getRepository(CompanySegment::class)->find($segmentId);
        $this->assertNull($deletedSegment, 'Segment should be deleted from database');
    }

    public function testDeleteActionReturnsErrorForNonExistentSegment(): void
    {
        $this->client->request(Request::METHOD_POST, '/s/company-segments/delete/99999');

        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->isSuccessful(),
            'Delete non-existent segment should redirect or show error'
        );
    }

    public function testBatchDeleteActionRemovesMultipleSegments(): void
    {
        $segment1 = $this->createCompanySegment('Batch Delete 1', 'batch-delete-1');
        $segment2 = $this->createCompanySegment('Batch Delete 2', 'batch-delete-2');
        $segment3 = $this->createCompanySegment('Batch Delete 3', 'batch-delete-3');

        $this->em->persist($segment1);
        $this->em->persist($segment2);
        $this->em->persist($segment3);
        $this->em->flush();

        $id1 = $segment1->getId();
        $id2 = $segment2->getId();
        $id3 = $segment3->getId();

        \assert(!in_array(null, [$id1, $id2, $id3], true));

        // Send POST request with JSON ids in query string
        $ids = json_encode([$id1, $id2, $id3]);
        $this->client->request(Request::METHOD_POST, '/s/company-segments/batchDelete?ids='.$ids);

        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->isSuccessful(),
            'Batch delete action should redirect or show success page'
        );

        // Verify all segments were deleted
        $this->em->clear();
        $this->assertNull($this->em->getRepository(CompanySegment::class)->find($id1));
        $this->assertNull($this->em->getRepository(CompanySegment::class)->find($id2));
        $this->assertNull($this->em->getRepository(CompanySegment::class)->find($id3));
    }

    public function testCloneActionCreatesNewSegment(): void
    {
        $original = $this->createCompanySegment('Original Segment', 'original-segment');
        $original->setDescription('Original description');
        $this->em->persist($original);
        $this->em->flush();

        $originalId = $original->getId();
        \assert(null !== $originalId);

        // Request the clone form
        $crawler = $this->client->request(Request::METHOD_GET, '/s/company-segments/clone/'.$originalId);

        // Form should be displayed with original data
        $this->assertResponseIsSuccessful('Clone form should return 200.');
        $this->assertStringContainsString('Original Segment', $crawler->filter('[name="company_segments"]')->html());

        // Submit the form with a new name
        $form = $crawler->filter('[name="company_segments"]')->form([
            'company_segments' => [
                'name'  => 'Cloned Segment',
                'alias' => 'cloned-segment',
            ],
        ]);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful('Clone form submission should succeed.');

        // Verify new segment was created in database
        $this->em->clear();
        $cloned = $this->em->getRepository(CompanySegment::class)->findOneBy(['alias' => 'cloned-segment']);
        $this->assertInstanceOf(CompanySegment::class, $cloned);
        $this->assertSame('Cloned Segment', $cloned->getName());
        $this->assertSame('Original description', $cloned->getDescription());

        // Original should still exist
        $originalStillExists = $this->em->getRepository(CompanySegment::class)->find($originalId);
        $this->assertInstanceOf(CompanySegment::class, $originalStillExists);
    }

    public function testCloneActionReturnsErrorForNonExistentSegment(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/company-segments/clone/99999');

        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->isSuccessful(),
            'Clone non-existent segment should redirect or show error'
        );
    }

    public function testCannotDeleteSegmentWithDependencies(): void
    {
        $this->loginAdminUser();

        // Create base segment
        $baseSegment = new CompanySegment();
        $baseSegment->setName('Base Segment');
        $baseSegment->setAlias('base-segment');
        $baseSegment->setPublicName('Base Segment');
        $baseSegment->setIsPublished(true);
        $this->em->persist($baseSegment);
        $this->em->flush();

        // Create dependent segment that references the base segment
        $dependentSegment = new CompanySegment();
        $dependentSegment->setName('Dependent Segment');
        $dependentSegment->setAlias('dependent-segment');
        $dependentSegment->setPublicName('Dependent Segment');
        $dependentSegment->setIsPublished(true);
        $dependentSegment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'company_segments',
                'object'     => 'company',
                'type'       => 'company_segments',
                'operator'   => 'in',
                'properties' => ['filter' => [$baseSegment->getId()]],
            ],
        ]);
        $this->em->persist($dependentSegment);
        $this->em->flush();

        // Try to delete the base segment (should fail)
        $crawler = $this->client->request(Request::METHOD_POST, '/s/company-segments/delete/'.$baseSegment->getId());
        $this->assertStringContainsString('cannot be deleted, it is required by', $crawler->text());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Check that segment still exists
        $this->em->clear();
        $segment = $this->em->getRepository(CompanySegment::class)->find($baseSegment->getId());
        $this->assertInstanceOf(CompanySegment::class, $segment);
    }

    public function testCannotBatchDeleteSegmentsWithDependencies(): void
    {
        $this->loginAdminUser();

        // Create base segment
        $baseSegment = new CompanySegment();
        $baseSegment->setName('Base Segment for Batch');
        $baseSegment->setAlias('base-segment-batch');
        $baseSegment->setPublicName('Base Segment');
        $baseSegment->setIsPublished(true);
        $this->em->persist($baseSegment);

        // Create independent segment (can be deleted)
        $independentSegment = new CompanySegment();
        $independentSegment->setName('Independent Segment');
        $independentSegment->setAlias('independent-segment');
        $independentSegment->setPublicName('Independent Segment');
        $independentSegment->setIsPublished(true);
        $this->em->persist($independentSegment);

        $this->em->flush();

        // Create dependent segment
        $dependentSegment = new CompanySegment();
        $dependentSegment->setName('Dependent Segment Batch');
        $dependentSegment->setAlias('dependent-segment-batch');
        $dependentSegment->setPublicName('Dependent Segment');
        $dependentSegment->setIsPublished(true);
        $dependentSegment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'company_segments',
                'object'     => 'company',
                'type'       => 'company_segments',
                'operator'   => 'in',
                'properties' => ['filter' => [$baseSegment->getId()]],
            ],
        ]);
        $this->em->persist($dependentSegment);
        $this->em->flush();

        $baseId        = $baseSegment->getId();
        $independentId = $independentSegment->getId();

        // Try to batch delete both segments
        $ids     = json_encode([$baseId, $independentId]);
        $crawler = $this->client->request(Request::METHOD_POST, '/s/company-segments/batchDelete?ids='.$ids);
        $this->assertStringContainsString('cannot be deleted', $crawler->text());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Check results
        $this->em->clear();
        $base        = $this->em->getRepository(CompanySegment::class)->find($baseId);
        $independent = $this->em->getRepository(CompanySegment::class)->find($independentId);

        // Base segment should still exist (has dependency)
        $this->assertInstanceOf(CompanySegment::class, $base);
        // Independent segment should be deleted
        $this->assertNull($independent);
    }

    private function loginAdminUser(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        \assert($user instanceof User);
        $this->loginUser($user);
    }
}
