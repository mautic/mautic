<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Extension\FormatterExtension;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormatterExtensionTest extends TestCase
{
    private FormatterExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FormatterExtension(
            new FormatterHelper(
                new DateHelper(
                    'F j, Y g:i a T',
                    'D, M d',
                    'F j, Y',
                    'g:i a',
                    $this->createMock(TranslatorInterface::class),
                    $this->createMock(CoreParametersHelper::class)
                ),
                $this->createMock(TranslatorInterface::class)
            )
        );
    }

    public function testItContainsAtLeastOneFilter(): void
    {
        $this->assertGreaterThan(0, $this->extension->getFilters());
    }

    public function testItContainsAtLeastOneFunction(): void
    {
        $this->assertGreaterThan(0, $this->extension->getFunctions());
    }

    public function testSimpleArrayToHtml(): void
    {
        $array = [
            'one' => 'one',
            'two' => 'two',
        ];

        $expected = 'one: one<br />two: two';

        $this->assertSame($expected, $this->extension->simpleArrayToHtml($array));
    }
}
