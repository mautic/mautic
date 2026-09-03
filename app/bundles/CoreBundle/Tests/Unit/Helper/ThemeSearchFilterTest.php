<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ThemeSearchFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ThemeSearchFilterTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;

    private ThemeSearchFilter $filter;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $themes;

    /**
     * @var list<string>
     */
    private array $scopeCommands;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.theme.searchcommand.feature' => 'feature',
                'mautic.core.theme.searchcommand.builder' => 'builder',
                default => $key,
            });

        $this->filter = new ThemeSearchFilter($this->translator);

        $this->scopeCommands = ['', 'feature', 'builder'];

        $this->themes = [
            'aurora' => [
                'name'   => 'Aurora',
                'key'    => 'aurora',
                'config' => [
                    'author'   => 'Mautic',
                    'features' => ['email'],
                    'builder'  => ['legacy'],
                ],
            ],
            'blank' => [
                'name'   => 'Blank',
                'key'    => 'blank',
                'config' => [
                    'author'   => 'Mautic',
                    'features' => ['email', 'page'],
                    'builder'  => ['grapesjsbuilder'],
                ],
            ],
        ];
    }

    public function testFilterStandardMatchesName(): void
    {
        $result = $this->filter->filter($this->themes, 'aurora', $this->scopeCommands);

        $this->assertArrayHasKey('aurora', $result);
        $this->assertArrayNotHasKey('blank', $result);
    }

    public function testFilterByFeature(): void
    {
        $result = $this->filter->filter($this->themes, 'feature:page', $this->scopeCommands);

        $this->assertArrayHasKey('blank', $result);
        $this->assertArrayNotHasKey('aurora', $result);
    }

    public function testFilterByBuilder(): void
    {
        $result = $this->filter->filter($this->themes, 'builder:grapesjsbuilder', $this->scopeCommands);

        $this->assertArrayHasKey('blank', $result);
        $this->assertArrayNotHasKey('aurora', $result);
    }
}
