<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\HttpFoundation\Request;

final class ReorderingDynamicContentTest extends MauticMysqlTestCase
{
    use DynamicContentReOrderingTrait;

    /**
     * @param array<string, int> $expectedOrder
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderWhileEditing')]
    public function testReorderingDynamicContentWhileEditing(string $orderValue, array $expectedOrder, bool $switchInitialOrder): void
    {
        $dwc1 = $this->createDynamicContent('DC-1', 'slot-Name', 0);
        $this->createDynamicContent('DC-2', 'slot-Name', 1);
        $this->createDynamicContent('DC-3', 'slot-Name', 2);
        $dwc4 = $this->createDynamicContent('DC-4', 'slot-Name', 3);

        $dwcId = $switchInitialOrder ? $dwc1->getId() : $dwc4->getId();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/dwc/edit/'.$dwcId);
        $dwcForm = $crawler->selectButton('Save & Close')->form();

        $dwcForm['dwc[displayOrder]']->setValue($orderValue);
        $this->client->submit($dwcForm);
        $this->assertResponseIsSuccessful();

        $this->assertDynamicContentOrder('slot-Name', $expectedOrder);
    }

    public function testReOrderingDynamicContentAfterDelete(): void
    {
        $dwc = $this->createDynamicContent('DC 1', 'slot-Name-1', 0);
        $this->createDynamicContent('DC 2', 'slot-Name-1', 1);
        $this->createDynamicContent('DC 3', 'slot-Name-1', 2);
        $this->createDynamicContent('DC 4', 'slot-Name-1', 3);

        $this->client->request(Request::METHOD_POST, '/s/dwc/delete/'.$dwc->getId());
        $this->assertResponseIsSuccessful();

        $dwcRepo = self::getContainer()->get(DynamicContentModel::class)->getRepository();
        $dwcList = $dwcRepo->getDynamicContentBySlotName('slot-Name-1');

        $this->assertCount(3, $dwcList);

        $this->assertEquals('DC 2', $dwcList[0]['name']);
        $this->assertEquals(1, $dwcList[0]['display_order']);

        $this->assertEquals('DC 3', $dwcList[1]['name']);
        $this->assertEquals(2, $dwcList[1]['display_order']);
    }

    public function testReorderingDynamicContentWhenSlotNameChanges(): void
    {
        // Create 3 DWC in slot-1 and 2 in slot-2
        $dwc1 = $this->createDynamicContent('DC-1', 'slot-1', 0);
        $this->createDynamicContent('DC-2', 'slot-1', 1);
        $this->createDynamicContent('DC-3', 'slot-1', 2);
        $this->createDynamicContent('DC-4', 'slot-2', 0);
        $this->createDynamicContent('DC-5', 'slot-2', 1);

        // Move DC-1 from slot-1 to slot-2, set display order to 1 (between DC-4 and DC-5)
        $crawler = $this->client->request(Request::METHOD_GET, '/s/dwc/edit/'.$dwc1->getId());
        $dwcForm = $crawler->selectButton('Save & Close')->form();
        $dwcForm['dwc[slotName]']->setValue('slot-2');
        $dwcForm['dwc[displayOrder]']->setValue('0');
        $this->client->submit($dwcForm);
        $this->assertResponseIsSuccessful();

        // slot-1 should now have DC-2 (1), DC-3 (2)
        $this->assertDynamicContentOrder('slot-1', [
            'DC-2' => 1,
            'DC-3' => 2,
        ]);

        // slot-2 should have DC-1 (1), DC-4 (2), DC-5 (3)
        $this->assertDynamicContentOrder('slot-2', [
            'DC-1' => 1,
            'DC-4' => 2,
            'DC-5' => 3,
        ]);
    }

    public function testReorderingDynamicContentWhenIsCampaignBasedChanged(): void
    {
        $dwc1 = $this->createDynamicContent('DC-1', 'slot-1', 0);
        $this->createDynamicContent('DC-2', 'slot-1', 1);
        $this->createDynamicContent('DC-3', 'slot-1', 2);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/dwc/edit/'.$dwc1->getId());
        $dwcForm = $crawler->selectButton('Save & Close')->form();
        $dwcForm['dwc[isCampaignBased]']->setValue('1');
        $this->client->submit($dwcForm);
        $this->assertResponseIsSuccessful();

        /** @var DynamicContentRepository $dwcRepo */
        $dwcRepo = self::getContainer()->get(DynamicContentModel::class)->getRepository();
        $dwc     = $dwcRepo->getEntity($dwc1->getId());
        $this->assertNull($dwc->getDisplayOrder(), 'Display Order should be null when isCampaignBased is set to true');

        $this->assertDynamicContentOrder('slot-1', [
            'DC-2' => 1,
            'DC-3' => 2,
        ]);
    }
}
