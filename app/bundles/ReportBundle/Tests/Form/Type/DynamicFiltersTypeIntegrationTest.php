<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Form\Type;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Form\Type\DynamicFiltersType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class DynamicFiltersTypeIntegrationTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);

        return [new PreloadedExtension([new DynamicFiltersType($translator)], [])];
    }

    public function testFilterWithValueIsSet(): void
    {
        $report = new Report();
        $report->setFilters([
            [
                'column'    => 'country',
                'condition' => 'eq',
                'dynamic'   => 1,
                'value'   => '1',
            ],
        ]);

        $form = $this->factory->create(DynamicFiltersType::class, null, [
            'report'            => $report,
            'filterDefinitions' => (object) [
                'definitions' => [
                    'country' => [
                        'alias' => 'country',
                        'type'  => 'boolean',
                        'label' => 'Country',
                    ],
                ],
            ],
        ]);

        $view = $form->createView();
        $this->assertSame(1, $view->children['country']->vars['data']);
    }

    public function testFilterWithDefaultValueIsSet(): void
    {
        $report = new Report();
        $report->setFilters([
            [
                'column'    => 'country',
                'condition' => 'eq',
                'dynamic'   => 1,
                'value'   => '',
            ],
        ]);

        $form = $this->factory->create(DynamicFiltersType::class, null, [
            'report'            => $report,
            'filterDefinitions' => (object) [
                'definitions' => [
                    'country' => [
                        'alias'        => 'country',
                        'type'         => 'boolean',
                        'label'        => 'Country',
                        'defaultValue' => '1',
                    ],
                ],
            ],
        ]);

        $view = $form->createView();
        $this->assertNull($view->children['country']->vars['data']);
    }

    public function testBooleanFilterIsSetCorrectly(): void
    {
        $report = new Report();
        $report->setFilters([
            [
                'column'    => 'country',
                'condition' => 'eq',
                'dynamic'   => 1,
                'value'   => '1',
            ],
        ]);

        $form = $this->factory->create(DynamicFiltersType::class, null, [
            'report'            => $report,
            'filterDefinitions' => (object) [
                'definitions' => [
                    'country' => [
                        'alias'        => 'country',
                        'type'         => 'boolean',
                        'label'        => 'Country',
                    ],
                ],
            ],
            'data' => [
                'country' => '0',
            ],
        ]);

        $view = $form->createView();
        $this->assertFalse($view->children['country']->vars['data']);
    }

    public function testPlaceholderIsSet(): void
    {
        $report = new Report();
        $report->setFilters([
            [
                'column'    => 'country',
                'condition' => 'eq',
                'dynamic'   => 1,
                'value'   => 'US',
            ],
        ]);

        $form = $this->factory->create(DynamicFiltersType::class, null, [
            'report'            => $report,
            'filterDefinitions' => (object) [
                'definitions' => [
                    'country' => [
                        'alias' => 'country',
                        'type'  => 'text',
                        'label' => 'Country',
                    ],
                ],
            ],
        ]);

        $view = $form->createView();
        $this->assertEquals('US', $view->children['country']->vars['attr']['placeholder']);
    }
}
