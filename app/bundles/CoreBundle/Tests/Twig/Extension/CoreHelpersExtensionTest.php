<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\CoreHelpersExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CoreHelpersExtensionTest extends TestCase
{
    private CoreHelpersExtension $extension;
    private MockObject|TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new CoreHelpersExtension($this->translator);
    }

    public function testGetFilterAttributesWithFilter(): void
    {
        $filter = [
            'placeholder'       => 'Custom Placeholder',
            'onchange'          => 'someFunction()',
            'prefix-exceptions' => ['prefix', 'exceptions'],
        ];

        $this->translator->expects($this->never())->method('trans');

        $attributes = $this->extension->getFilterAttributes('my_filter', $filter, 'target', 'template');

        $this->assertSame('my_filter', $attributes['id']);
        $this->assertSame('my_filter', $attributes['name']);
        $this->assertSame('template', $attributes['data-tmpl']);
        $this->assertSame('Custom Placeholder', $attributes['data-placeholder']);
        $this->assertSame('someFunction()', $attributes['onchange']);
        $this->assertSame('prefix,exceptions', $attributes['data-prefix-exceptions']);
    }

    public function testGetFilterAttributesWithoutFilter(): void
    {
        $filter = []; // No placeholder, no onchange, no target

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('mautic.core.list.filter')
            ->willReturn('Default Placeholder');

        $attributes = $this->extension->getFilterAttributes('f', $filter, 'target-div', 'tmpl-id');

        $this->assertSame('Default Placeholder', $attributes['data-placeholder']);
        $this->assertSame('listfilter', $attributes['data-toggle']);
        $this->assertSame('target-div', $attributes['data-target']);
    }
}
