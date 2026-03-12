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

    private function loginAdminUser(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        \assert($user instanceof User);
        $this->loginUser($user);
    }
}
