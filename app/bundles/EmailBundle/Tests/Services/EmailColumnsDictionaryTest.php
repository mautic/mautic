<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Services\EmailColumnsDictionary;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailColumnsDictionaryTest extends TestCase
{
    public function testConfiguredColumnsAreReturnedInConfiguredOrder(): void
    {
        $dictionary = $this->createDictionary(['id', 'name']);

        $this->assertSame(
            [
                'id'   => 'mautic.core.id',
                'name' => 'mautic.core.name',
            ],
            $dictionary->getColumns()
        );
    }

    public function testDefaultColumnsAreUsedWhenConfiguredColumnsAreEmpty(): void
    {
        $dictionary = $this->createDictionary([]);

        $this->assertSame(
            ['name', 'category', 'template', 'stats', 'dateAdded', 'dateModified', 'createdByUser', 'id'],
            array_keys($dictionary->getColumns())
        );
    }

    public function testDefaultColumnsAreUsedWhenConfiguredColumnsAreInvalid(): void
    {
        $dictionary = $this->createDictionary(['does_not_exist']);

        $this->assertSame(
            ['name', 'category', 'template', 'stats', 'dateAdded', 'dateModified', 'createdByUser', 'id'],
            array_keys($dictionary->getColumns())
        );
    }

    /**
     * @param string[] $configuredColumns
     */
    private function createDictionary(array $configuredColumns): EmailColumnsDictionary
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->with('email_columns')->willReturn($configuredColumns);

        return new EmailColumnsDictionary($translator, $coreParametersHelper);
    }
}
