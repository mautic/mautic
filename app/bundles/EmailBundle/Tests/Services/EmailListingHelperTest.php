<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Services;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Services\EmailListingHelper;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailListingHelperTest extends TestCase
{
    public function testUpdatedFiltersAreParsedByFilterType(): void
    {
        $this->assertSame(
            [
                'list'     => ['1'],
                'category' => ['2'],
                'theme'    => ['blank'],
                'unknown'  => ['value'],
            ],
            $this->createHelper()->getUpdatedFilters(json_encode(['list:1', 'category:2', 'theme:blank', 'unknown:value']))
        );
    }

    public function testInvalidUpdatedFiltersPayloadReturnsEmptyFilters(): void
    {
        $this->assertSame([], $this->createHelper()->getUpdatedFilters('not-json'));
    }

    public function testCurrentFiltersAddForcedListCategoryAndThemeFilters(): void
    {
        $listFilters = [
            'filters' => [
                'placeholder' => 'Search',
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.lists'      => [],
                    'mautic.core.filter.categories' => [],
                    'mautic.core.filter.themes'     => [],
                ],
            ],
        ];
        $filter = [
            'string' => 'subject list:1 list:customers category:2 theme:blank',
            'force'  => [],
        ];

        $ignoreListJoin = $this->createHelper([
            ['id' => 1, 'alias' => 'customers'],
        ])->applyCurrentFilters(
            [
                'list'     => ['1'],
                'category' => ['2'],
                'theme'    => ['blank'],
                'unknown'  => ['value'],
            ],
            $listFilters,
            $filter
        );

        $this->assertFalse($ignoreListJoin);
        $this->assertSame('subject', $filter['string']);
        $this->assertSame(['1'], $listFilters['filters']['groups']['mautic.core.filter.lists']['values']);
        $this->assertSame(['2'], $listFilters['filters']['groups']['mautic.core.filter.categories']['values']);
        $this->assertSame(['blank'], $listFilters['filters']['groups']['mautic.core.filter.themes']['values']);
        $this->assertSame(
            [
                ['column' => 'l.alias', 'expr' => 'in', 'value' => ['customers']],
                ['column' => 'c.id', 'expr' => 'in', 'value' => [2]],
                ['column' => 'e.template', 'expr' => 'in', 'value' => ['blank']],
            ],
            $filter['force']
        );
    }

    public function testCurrentFiltersWithoutListKeepListJoinIgnored(): void
    {
        $listFilters = [
            'filters' => [
                'placeholder' => 'Search',
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.categories' => [],
                ],
            ],
        ];
        $filter = [
            'string' => '',
            'force'  => [],
        ];

        $this->assertTrue($this->createHelper()->applyCurrentFilters(['category' => ['2']], $listFilters, $filter));
    }

    public function testStartIsNeverNegative(): void
    {
        $helper = $this->createHelper();

        $this->assertSame(0, $helper->getStart(1, 30));
        $this->assertSame(30, $helper->getStart(2, 30));
        $this->assertSame(0, $helper->getStart(0, 30));
    }

    public function testLastPageIsReturnedWhenCurrentPageIsOutOfBounds(): void
    {
        $helper = $this->createHelper();

        $this->assertNull($helper->getLastPageIfCurrentPageIsOutOfBounds(0, 30, 30));
        $this->assertNull($helper->getLastPageIfCurrentPageIsOutOfBounds(31, 30, 30));
        $this->assertSame(1, $helper->getLastPageIfCurrentPageIsOutOfBounds(1, 30, 30));
        $this->assertSame(2, $helper->getLastPageIfCurrentPageIsOutOfBounds(2, 2, 1));
    }

    /**
     * @param array<int, array{id: int, alias: string}> $lists
     */
    private function createHelper(array $lists = []): EmailListingHelper
    {
        $leadListModel = $this->createMock(ListModel::class);
        $leadListModel->method('getUserLists')->willReturn($lists);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new EmailListingHelper(
            $this->createStub(CorePermissions::class),
            $leadListModel,
            $translator
        );
    }
}
