<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class ListQuickFiltersTemplateTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/Resources/views');
        $this->twig = new Environment($loader);

        $this->twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
    }

    public function testFirstRenderedGroupGetsTopMarginWhenEarlierGroupHasNoFilters(): void
    {
        $output = $this->render([
            'myplugin.quick_filters.empty'  => ['conflictGroup' => true],
            'myplugin.quick_filters.status' => ['conflictGroup' => true, 'filters' => [$this->filter('ispublished')]],
            'myplugin.quick_filters.owner'  => ['conflictGroup' => true, 'filters' => [$this->filter('ismine')]],
        ]);

        $this->assertStringContainsString('<div class="dropdown-header mt-0">myplugin.quick_filters.status</div>', $output);
        $this->assertStringContainsString('<div class="dropdown-header">myplugin.quick_filters.owner</div>', $output);
        $this->assertStringNotContainsString('myplugin.quick_filters.empty', $output);
        $this->assertStringContainsString('data-filter="ispublished"', $output);
    }

    public function testGroupsSharingAShortNameKeepSeparateConflictGroups(): void
    {
        $output = $this->render([
            'mautic.core.quick_filters.status' => ['conflictGroup' => true, 'filters' => [$this->filter('ispublished')]],
            'myplugin.quick_filters.status'    => ['conflictGroup' => true, 'filters' => [$this->filter('ismine')]],
        ]);

        $this->assertStringContainsString('data-conflict-group="mautic.core.quick_filters.status"', $output);
        $this->assertStringContainsString('data-conflict-group="myplugin.quick_filters.status"', $output);
    }

    public function testLegacyArrayStillUsesBuiltInConflictGroups(): void
    {
        $output = $this->render([$this->filter('ispublished'), $this->filter('ismine')]);

        $this->assertStringContainsString('data-conflict-group="status"', $output);
        $this->assertStringContainsString('data-conflict-group="ownership"', $output);
    }

    /**
     * @param array<mixed> $quickFilters
     */
    private function render(array $quickFilters): string
    {
        return $this->twig->load('Helper/list_quickfilters.html.twig')->render(['quickFilters' => $quickFilters]);
    }

    /**
     * @return array{search: string, label: string, icon: string, tooltip: string}
     */
    private function filter(string $search): array
    {
        return ['search' => $search, 'label' => $search, 'icon' => 'ri-filter-line', 'tooltip' => $search];
    }
}
