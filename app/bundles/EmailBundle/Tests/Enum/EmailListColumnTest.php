<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Enum;

use Mautic\EmailBundle\Enum\EmailListColumn;
use PHPUnit\Framework\TestCase;

final class EmailListColumnTest extends TestCase
{
    public function testDefaultValuesReturnExpectedColumnsInExpectedOrder(): void
    {
        $this->assertSame(['name', 'category', 'template', 'stats', 'dateAdded', 'dateModified', 'createdByUser', 'id'], EmailListColumn::defaultValues());
    }

    public function testLabelKeysMatchExpectedTranslations(): void
    {
        $this->assertSame('mautic.core.name', EmailListColumn::Name->labelKey());
        $this->assertSame('mautic.core.stats', EmailListColumn::Stats->labelKey());
        $this->assertSame('mautic.lead.import.label.dateModified', EmailListColumn::DateModified->labelKey());
    }

    public function testHeaderMetaExposesSortableAndNonSortableColumns(): void
    {
        $this->assertSame([
            'orderBy' => 'e.name',
            'class'   => 'col-email-name',
        ], EmailListColumn::Name->headerMeta());

        $this->assertSame([
            'class' => 'visible-sm visible-md visible-lg col-email-stats',
        ], EmailListColumn::Stats->headerMeta());

        $this->assertSame([
            'orderBy'    => 'e.dateModified',
            'defaultDir' => 'DESC',
            'default'    => true,
            'class'      => 'visible-lg col-email-dateModified',
        ], EmailListColumn::DateModified->headerMeta());
    }

    public function testEachColumnDefinesAHeaderClass(): void
    {
        foreach (EmailListColumn::cases() as $column) {
            $this->assertArrayHasKey('class', $column->headerMeta(), $column->value);
            $this->assertNotSame('', $column->headerMeta()['class'], $column->value);
        }
    }
}
