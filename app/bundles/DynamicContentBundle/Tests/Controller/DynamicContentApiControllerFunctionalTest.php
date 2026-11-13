<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentLeadData;
use Mautic\DynamicContentBundle\Tests\Functional\DynamicContentReOrderingTrait;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DynamicContentApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use DynamicContentReOrderingTrait;

    public function testDwcGetEndpointForNoSlotNorContact(): void
    {
        $this->client->request(Request::METHOD_GET, '/dwc/slot-a');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getContent());
    }

    public function testDwcGetEndpointForASlotAndContact(): void
    {
        $contact = new Lead();
        $contact->setEmail('johana@doe.email');

        $dwc = new DynamicContent();
        $dwc->setContent('<some>content</some>');
        $dwc->setName('Slot A');
        $dwc->setSlotName('slot-a');

        $dwcContact = new DynamicContentLeadData();
        $dwcContact->setDateAdded(new \DateTime());
        $dwcContact->setDynamicContent($dwc);
        $dwcContact->setLead($contact);
        $dwcContact->setSlot($dwc->getSlotName());

        $stat = new Stat();
        $stat->setLead($contact);
        $stat->setTrackingHash('tracking-hash-1');
        $stat->setEmailAddress($contact->getEmail());
        $stat->setDateSent(new \DateTime());

        $this->em->persist($contact);
        $this->em->persist($stat);
        $this->em->persist($dwc);
        $this->em->persist($dwcContact);
        $this->em->flush();

        $ct = ClickthroughHelper::encodeArrayForUrl(['stat' => 'tracking-hash-1']);

        $this->client->request(Request::METHOD_GET, "/dwc/slot-a?ct={$ct}");

        $this->assertResponseIsSuccessful();

        $responseArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('<some>content</some>', $responseArray['content']);
    }

    public function testCreateDwc(): void
    {
        $payload = [
            'name'    => 'API test',
            'content' => 'API test',
        ];

        $this->client->request(Request::METHOD_POST, '/api/dynamiccontents/new', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, $this->client->getResponse()->getContent());
    }

    public function testDynamicContentValidation(): void
    {
        $payload = [
            'name'            => 'New Dynamic Content',
            'isPublished'     => true,
            'isCampaignBased' => 0,
            'slotName'        => 'test-slot-Name',
            'type'            => TypeList::HTML,
            'language'        => 'en',
            'filters'         => [
                [
                    'glue'     => 'and',
                    'field'    => 'city',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'filter'   => 'Pune',
                    'display'  => null,
                    'operator' => '=',
                ],
            ],
        ];

        $this->client->request('POST', '/api/dynamiccontents/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertNotEmpty($response['errors']);
    }

    /**
     * @param array<string, int> $expectedOrder
     */
    #[DataProvider('dataProviderWhileAdding')]
    public function testReOrderingNewDwcViaApi(string $orderValue, array $expectedOrder): void
    {
        $this->createDynamicContent('DC-1', 'slot-Name', 0);
        $this->createDynamicContent('DC-2', 'slot-Name', 1);
        $this->createDynamicContent('DC-3', 'slot-Name', 2);

        $payload = [
            'name'            => 'DC-4',
            'isPublished'     => true,
            'isCampaignBased' => 0,
            'slotName'        => 'slot-Name',
            'displayOrder'    => $orderValue,
            'type'            => TypeList::HTML,
            'language'        => 'en',
            'filters'         => [
                [
                    'glue'     => 'and',
                    'field'    => 'city',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'filter'   => 'Pune',
                    'display'  => null,
                    'operator' => '=',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/dynamiccontents/new', $payload);
        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($response['dynamicContent']);
        $this->assertSame('DC-4', $response['dynamicContent']['name']);

        $this->assertDynamicContentOrder('slot-Name', $expectedOrder);
    }

    /**
     * @param array<string, int> $expectedOrder
     */
    #[DataProvider('dataProviderWhileEditing')]
    public function testReOrderingForExistingDwcViaApi(string $orderValue, array $expectedOrder, bool $switchInitialOrder): void
    {
        $dwc1 = $this->createDynamicContent('DC-1', 'slot-Name', 0);
        $this->createDynamicContent('DC-2', 'slot-Name', 1);
        $this->createDynamicContent('DC-3', 'slot-Name', 2);
        $dwc4 = $this->createDynamicContent('DC-4', 'slot-Name', 3);

        $dwcId   = $switchInitialOrder ? $dwc1->getId() : $dwc4->getId();
        $dwcName = $switchInitialOrder ? $dwc1->getName() : $dwc4->getName();

        $payload = [
            'isCampaignBased' => 0,
            'slotName'        => 'slot-Name',
            'displayOrder'    => $orderValue,
        ];

        $this->client->request(Request::METHOD_PATCH, "/api/dynamiccontents/{$dwcId}/edit", $payload);
        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($response['dynamicContent']);
        $this->assertSame($dwcName, $response['dynamicContent']['name']);
    }

    public function testHtmlContentIsNotStrippedForHtmlType(): void
    {
        $payload = [
            'name'            => 'API Test Dynamic Content',
            'isPublished'     => true,
            'isCampaignBased' => 1,
            'type'            => 'html',
            'language'        => 'en',
            'content'         => '<p>Test content</p>',
        ];
        $this->client->request('POST', '/api/dynamiccontents/new', $payload);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $dynamicContent = $response['dynamicContent'] ?? null;
        $this->assertNotNull($dynamicContent);
        $this->assertSame('<p>Test content</p>', $dynamicContent['content']);
        $this->assertSame('html', $dynamicContent['type']);
    }

    #[DataProvider('sourceUriTrackingProvider')]
    public function testApiEndpointSourceUriTracking(string $uri): void
    {
        $this->createTestDynamicContent('API Source URI Test', 'api-source-test-slot');

        $this->client->request('GET', '/dwc/api-source-test-slot', ['page_url' => $uri]);

        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [200, 204],
            'API should handle page_url parameter and return valid status code'
        );

        if (200 === $this->client->getResponse()->getStatusCode()) {
            $this->assertNotEmpty($this->client->getResponse()->getContent());
        }
    }

    /**
     * Test that API endpoint works correctly without page_url parameter.
     */
    public function testApiEndpointBackwardCompatibility(): void
    {
        $this->createTestDynamicContent('Backward Compatibility Test', 'backward-compat-slot');

        $this->client->request('GET', '/dwc/backward-compat-slot');

        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [200, 204],
            'API should work without page_url parameter (backward compatibility)'
        );
    }

    /**
     * Test malformed URLs are handled gracefully.
     */
    public function testApiEndpointHandlesMalformedUrls(): void
    {
        $this->createTestDynamicContent('Malformed URL Test', 'malformed-url-slot');

        $malformedUrls = [
            'not-a-url',
            'ftp://invalid-protocol.com',
            '',
            'javascript:alert("xss")',
        ];

        foreach ($malformedUrls as $malformedUrl) {
            $this->client->request('GET', '/dwc/malformed-url-slot', ['page_url' => $malformedUrl]);

            $this->assertContains(
                $this->client->getResponse()->getStatusCode(),
                [200, 204],
                "API should handle malformed URL gracefully: {$malformedUrl}"
            );
        }
    }

    /**
     * @return \Iterator<string, array<int, string>>
     */
    public static function sourceUriTrackingProvider(): \Iterator
    {
        yield 'simple URL' => [
            'https://example.com/page1',
            'https://example.com/page1',
        ];
        yield 'URL with query params' => [
            'https://example.com/page?utm_source=google&utm_medium=cpc',
            'https://example.com/page?utm_source=google&utm_medium=cpc',
        ];
        yield 'URL with fragment' => [
            'https://example.com/page#section1',
            'https://example.com/page#section1',
        ];
        yield 'complex URL' => [
            'https://subdomain.example.com:8080/path/to/page?param=value&another=test#anchor',
            'https://subdomain.example.com:8080/path/to/page?param=value&another=test#anchor',
        ];
    }

    private function createTestDynamicContent(string $name, string $slotName): DynamicContent
    {
        $dwc = new DynamicContent();
        $dwc->setName($name)
            ->setSlotName($slotName)
            ->setContent('<p>Test content for source URI tracking</p>')
            ->setIsCampaignBased(false)
            ->setIsPublished(true)
            ->setFilters([])
            ->setDisplayOrder(1);

        $this->em->persist($dwc);
        $this->em->flush();

        return $dwc;
    }
}
