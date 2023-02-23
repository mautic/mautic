<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\CoreBundle\Templating\Twig\Extension\FormatterExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormatterExtensionTest extends TestCase
{
    public function testItContainsAtLeastOneFilter(): void
    {
        $extension = new FormatterExtension($this->createMock(FormatterHelper::class));

        $this->assertGreaterThan(0, $extension->getFilters());
    }

    public function testItContainsAtLeastOneFunction(): void
    {
        $extension = new FormatterExtension($this->createMock(FormatterHelper::class));

        $this->assertGreaterThan(0, $extension->getFunctions());
    }

    public function testSimpleArrayToHtml(): void
    {
        $extension = new FormatterExtension(
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

        $array = [
            'one' => 'one',
            'two' => 'two',
        ];

        $expected = 'one: one<br />two: two';

        $this->assertSame($expected, $extension->simpleArrayToHtml($array));
    }
}
