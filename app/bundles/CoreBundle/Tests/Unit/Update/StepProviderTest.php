<?php

namespace Mautic\CoreBundle\Tests\Unit\Update;

use Mautic\CoreBundle\Update\Step\StepInterface;
use Mautic\CoreBundle\Update\StepProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StepProviderTest extends TestCase
{
    /**
     * @var StepProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new StepProvider();

        /** @var MockObject|StepInterface $step1 */
        $step1 = $this->createMock(StepInterface::class);
        $step1->method('getOrder')
            ->willReturn(10);
        $step1->method('shouldExecuteInFinalStage')
            ->willReturn(false);
        $this->provider->addStep($step1);

        /** @var MockObject|StepInterface $step2 */
        $step2 = $this->createMock(StepInterface::class);
        $step2->method('getOrder')
            ->willReturn(0);
        $step2->method('shouldExecuteInFinalStage')
            ->willReturn(false);
        $this->provider->addStep($step2);

        /** @var MockObject|StepInterface $step3 */
        $step3 = $this->createMock(StepInterface::class);
        $step3->method('getOrder')
            ->willReturn(50);
        $step3->method('shouldExecuteInFinalStage')
            ->willReturn(true);
        $this->provider->addStep($step3);

        /** @var MockObject|StepInterface $step4 */
        $step4 = $this->createMock(StepInterface::class);
        $step4->method('getOrder')
            ->willReturn(30);
        $step4->method('shouldExecuteInFinalStage')
            ->willReturn(true);
        $this->provider->addStep($step4);
    }

    public function testInitialStepsAreOrdered()
    {
        $steps = $this->provider->getInitialSteps();
        $this->assertCount(2, $steps);
        $this->assertEquals(0, $steps[0]->getOrder());
        $this->assertEquals(10, $steps[1]->getOrder());
    }

    public function testsFinalStepsAreOrdered()
    {
        $steps = $this->provider->getFinalSteps();
        $this->assertCount(2, $steps);
        $this->assertEquals(30, $steps[0]->getOrder());
        $this->assertEquals(50, $steps[1]->getOrder());
    }
}
