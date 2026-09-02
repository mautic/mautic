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
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DynamicContentApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use IsolatedTestTrait;
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

        self::assertResponseIsSuccessful($this->client->getResponse()->getContent());

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
        Assert::assertNotEmpty($response['errors']);
    }

    /**
     * @param array<string, int> $expectedOrder
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderWhileAdding')]
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
        Assert::assertNotEmpty($response['dynamicContent']);
        Assert::assertSame('DC-4', $response['dynamicContent']['name']);

        $this->assertDynamicContentOrder('slot-Name', $expectedOrder);
    }

    /**
     * @param array<string, int> $expectedOrder
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderWhileEditing')]
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
        Assert::assertNotEmpty($response['dynamicContent']);
        Assert::assertSame($dwcName, $response['dynamicContent']['name']);
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
}
