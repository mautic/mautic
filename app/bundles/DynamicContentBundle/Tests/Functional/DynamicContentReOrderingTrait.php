<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use PHPUnit\Framework\Assert;

trait DynamicContentReOrderingTrait
{
    public static function dataProviderWhileAdding(): \Generator
    {
        yield ['0', ['DC-4' => 1, 'DC-1' => 2, 'DC-2' => 3, 'DC-3' => 4]]; // Put at beginning
        yield ['3', ['DC-1' => 1, 'DC-2' => 2, 'DC-3' => 3, 'DC-4' => 4]]; // Put at end
        yield ['1', ['DC-1' => 1, 'DC-4' => 2, 'DC-2' => 3, 'DC-3' => 4]]; // Put in middle
    }

    public static function dataProviderWhileEditing(): \Generator
    {
        yield ['0', ['DC-4' => 1, 'DC-1' => 2, 'DC-2' => 3, 'DC-3' => 4], false]; // Put at beginning
        yield ['4', ['DC-2' => 1, 'DC-3' => 2, 'DC-4' => 3, 'DC-1' => 4], true]; // Put at end
        yield ['1', ['DC-1' => 1, 'DC-4' => 2, 'DC-2' => 3, 'DC-3' => 4], false]; // Put from higher order to lower order
        yield ['3', ['DC-2' => 1, 'DC-3' => 2, 'DC-1' => 3, 'DC-4' => 4], true]; // Put from lower order to higher order
    }

    private function createDynamicContent(
        string $name,
        string $slotName,
        int $order, string $content = '<p> some content </p>',
    ): DynamicContent {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'city',
                'object'   => 'lead',
                'type'     => 'text',
                'filter'   => 'Pune',
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $dwc = new DynamicContent();
        $dwc->setIsPublished(true)
            ->setName($name)
            ->setContent($content)
            ->setIsCampaignBased(false)
            ->setFilters($filters)
            ->setSlotName($slotName)
            ->setDisplayOrder($order);

        $model = $this->getContainer()->get(DynamicContentModel::class);
        \assert($model instanceof DynamicContentModel);
        $model->saveEntity($dwc);

        return $dwc;
    }

    /**
     * @param array<string, int> $expectedOrder
     */
    private function assertDynamicContentOrder(string $slotName, array $expectedOrder): void
    {
        $dwcRepo     = $this->getContainer()->get(DynamicContentModel::class)->getRepository();
        $dwcList     = $dwcRepo->getDynamicContentBySlotName($slotName);
        $actualOrder = array_column($dwcList, 'display_order', 'name');
        Assert::assertEquals($expectedOrder, $actualOrder, print_r($actualOrder, true));
    }
}
