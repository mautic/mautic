<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Helper;

use Mautic\ReportBundle\Helper\ReportHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ReportHelperTest extends TestCase
{
    private ReportHelper $reportHelper;

    protected function setUp(): void
    {
        $this->reportHelper = new ReportHelper($this->createMock(EventDispatcherInterface::class));
    }

    public function testGetStandardColumnsMethodReturnsCorrectColumns(): void
    {
        $columns = $this->reportHelper->getStandardColumns('somePrefix');

        $expectedColumnns = [
            'somePrefixid' => [
                'label' => 'mautic.core.id',
                'type'  => 'int',
                'alias' => 'somePrefixid',
            ],
            'somePrefixname' => [
                'label' => 'mautic.core.name',
                'type'  => 'string',
                'alias' => 'somePrefixname',
            ],
            'somePrefixcreated_by_user' => [
                'label' => 'mautic.core.createdby',
                'type'  => 'string',
                'alias' => 'somePrefixcreated_by_user',
            ],
            'somePrefixdate_added' => [
                'label' => 'mautic.report.field.date_added',
                'type'  => 'datetime',
                'alias' => 'somePrefixdate_added',
            ],
            'somePrefixmodified_by_user' => [
                'label' => 'mautic.report.field.modified_by_user',
                'type'  => 'string',
                'alias' => 'somePrefixmodified_by_user',
            ],
            'somePrefixdate_modified' => [
                'label' => 'mautic.report.field.date_modified',
                'type'  => 'datetime',
                'alias' => 'somePrefixdate_modified',
            ],
            'somePrefixdescription' => [
                'label' => 'mautic.core.description',
                'type'  => 'string',
                'alias' => 'somePrefixdescription',
            ],
            'somePrefixpublish_up' => [
                'label' => 'mautic.report.field.publish_up',
                'type'  => 'datetime',
                'alias' => 'somePrefixpublish_up',
            ],
            'somePrefixpublish_down' => [
                'label' => 'mautic.report.field.publish_down',
                'type'  => 'datetime',
                'alias' => 'somePrefixpublish_down',
            ],
            'somePrefixis_published' => [
                'label' => 'mautic.report.field.is_published',
                'type'  => 'bool',
                'alias' => 'somePrefixis_published',
            ],
        ];

        $this->assertEquals($expectedColumnns, $columns);
    }
}
