<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ReorderingDynamicContentTest extends MauticMysqlTestCase
{
    use DynamicContentReOrderingTrait;

    /**
     * @param array<string, int> $expectedOrder
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderWhileAdding')]
    public function testReorderingDynamicContentWhileAdding(string $orderValue, array $expectedOrder): void
    {
        $this->createDynamicContent('DC-1', 'slot-Name', 0);
        $this->createDynamicContent('DC-2', 'slot-Name', 1);
        $this->createDynamicContent('DC-3', 'slot-Name', 2);

        $crawler = $this->client->request('GET', '/s/dwc/new');
        $form    = $crawler->selectButton('Save')->form();
        $payload = [
            'dwc' => [
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
                '_token' => $form->getValues()['dwc[_token]'],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/s/dwc/new', $payload, [], $this->createAjaxHeaders());
        echo $this->client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        $this->assertDynamicContentOrder('slot-Name', $expectedOrder);
    }

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

        $dwcRepo = self::getContainer()->get('mautic.dynamicContent.model.dynamicContent')->getRepository();
        $dwcList = $dwcRepo->getDynamicContentBySlotName('slot-Name-1');

        Assert::assertEquals(3, count($dwcList));

        Assert::assertEquals('DC 2', $dwcList[0]['name']);
        Assert::assertEquals(1, $dwcList[0]['display_order']);

        Assert::assertEquals('DC 3', $dwcList[1]['name']);
        Assert::assertEquals(2, $dwcList[1]['display_order']);
    }
}
