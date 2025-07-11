<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Form\Type\SegmentConfigType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

final class SegmentConfigTypeTest extends TestCase
{
    private SegmentConfigType $segmentConfigType;

    /**
     * @var FormBuilderInterface<FormBuilderInterface>&MockObject
     */
    private MockObject $formBuilderInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentConfigType    = new SegmentConfigType();
        $this->formBuilderInterface = $this->createMock(FormBuilderInterface::class);
    }

    public function testThatGetBlockPrefixReturnsAValue(): void
    {
        $blockPrefix = $this->segmentConfigType->getBlockPrefix();
        $this->assertNotEmpty($blockPrefix);
    }

    public function testThatBuildFormMethodAddsSegmentBuildAndRebuildTimeWarningOption(): void
    {
        $rebuildParameters = [
            'label'      => 'mautic.lead.list.form.config.segment_rebuild_time_warning',
            'label_attr' => [
                'class' => 'control-label',
            ],
            'attr' => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.lead.list.form.config.segment_rebuild_time_warning.tooltip',
            ],
            'required' => false,
        ];

        $buildParameters = [
            'label'      => 'mautic.lead.list.form.config.segment_build_time_warning',
            'label_attr' => [
                'class' => 'control-label',
            ],
            'attr' => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.lead.list.form.config.segment_build_time_warning.tooltip',
            ],
            'required' => false,
        ];
        $matcher = $this->exactly(2);

        $this->formBuilderInterface->expects($matcher)
            ->method('add')->willReturnCallback(function (...$parameters) use ($matcher, $rebuildParameters, $buildParameters) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('segment_rebuild_time_warning', $parameters[0]);
                    $this->assertSame(NumberType::class, $parameters[1]);
                    $this->assertSame($rebuildParameters, $parameters[2]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('segment_build_time_warning', $parameters[0]);
                    $this->assertSame(NumberType::class, $parameters[1]);
                    $this->assertSame($buildParameters, $parameters[2]);
                }

                return $this->formBuilderInterface;
            });

        $this->segmentConfigType->buildForm($this->formBuilderInterface, []);
    }
}
