<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DynamicContentVariationsTabTest extends MauticMysqlTestCase
{
    private string $slotName = 'test_slot';

    private DynamicContent $mainDwc;

    protected function setUp(): void
    {
        parent::setUp();

        // Create main DWC entity with slot name
        $this->mainDwc = $this->createDynamicContent('Main DWC', $this->slotName, 10);

        // Create several variations with different display orders
        $this->createDynamicContent('Variation 1', $this->slotName, 20);
        $this->createDynamicContent('Variation 2', $this->slotName, 30);
        $this->createDynamicContent('Variation 3', $this->slotName, 15);

        $this->em->flush();
    }

    public function testVariationsTabExists(): void
    {
        // Request the view page for the main DWC entity
        $this->client->request(Request::METHOD_GET, '/s/dwc/view/'.$this->mainDwc->getId());

        // Ensure response is OK
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Check if the variations tab exists
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('href="#variations-container"', (string) $content);
    }

    public function testVariationsTabLoadsCorrectly(): void
    {
        // Request the variations tab content directly
        $crawler = $this->client->request(
            Request::METHOD_GET,
            sprintf('/s/dwc/view/%d#variations-container', $this->mainDwc->getId())
        );

        // Ensure response is OK
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Check if the variations table exists and has the correct number of rows
        $tableRows = $crawler->filter('#dwcVariationsTable tbody tr');

        // 4 variations including the main one
        $this->assertCount(4, $tableRows);

        // Check if the content shows the variation names
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Variation 1', (string) $content);
        $this->assertStringContainsString('Variation 2', (string) $content);
        $this->assertStringContainsString('Variation 3', (string) $content);

        // Check if the current entity is also present with the current label
        $this->assertStringContainsString('Main DWC', (string) $content);
    }

    public function testVariationsAreSortedByDisplayOrderByDefault(): void
    {
        // Request the variations tab content directly
        $crawler = $this->client->request(
            Request::METHOD_GET,
            sprintf('/s/dwc/view/%d#variations-container', $this->mainDwc->getId())
        );

        // Get all rows and extract display orders
        $displayOrders = $crawler->filter('#dwcVariationsTable tbody tr td:nth-child(2)')->each(fn($node): int => (int) $node->text());

        // Check if the variations are sorted by display_order in DESC order
        $sortedOrders = $displayOrders;
        rsort($sortedOrders);

        // The order should be 30, 20, 15, 10 (DESC) with 10 being the main DWC
        $this->assertEquals($sortedOrders, $displayOrders);
    }

    public function testVariationsTabNotShownWithSingleEntity(): void
    {
        // Create a new dynamic content with unique slot name (so it's the only one with this slot)
        $uniqueSlotDwc = $this->createDynamicContent('Unique Slot DWC', 'unique_slot', 10);
        $this->em->flush();

        // Request the view page for this unique entity
        $this->client->request(Request::METHOD_GET, '/s/dwc/view/'.$uniqueSlotDwc->getId());

        // Ensure response is OK
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Check that variations tab does NOT exist since there are no other variations
        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('href="#variations-container"', (string) $content);
    }

    /**
     * Helper method to create a new DynamicContent entity.
     */
    private function createDynamicContent(string $name, string $slotName, int $displayOrder): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName($name);
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setSlotName($slotName);
        $dynamicContent->setDisplayOrder($displayOrder);
        $dynamicContent->setContent('Sample content for '.$name);
        $dynamicContent->setIsPublished(true);

        $this->em->persist($dynamicContent);

        return $dynamicContent;
    }
}
