<?php

namespace Mautic\CoreBundle\Update;

use Mautic\CoreBundle\Update\Step\StepInterface;

class StepProvider
{
    /**
     * @var StepInterface[]
     */
    private array $initialSteps = [];

    /**
     * @var StepInterface[]
     */
    private array $finalSteps = [];

    public function addStep(StepInterface $step): void
    {
        if ($step->shouldExecuteInFinalStage()) {
            $this->finalSteps[] = $step;

            return;
        }

        $this->initialSteps[] = $step;
    }

    /**
     * @return StepInterface[]
     */
    public function getInitialSteps(): array
    {
        return $this->orderSteps($this->initialSteps);
    }

    /**
     * @return StepInterface[]
     */
    public function getFinalSteps(): array
    {
        return $this->orderSteps($this->finalSteps);
    }

    /**
     * @return StepInterface[]
     */
    private function orderSteps(array $steps): array
    {
        usort($steps, fn (StepInterface $step1, StepInterface $step2): int => $step1->getOrder() <=> $step2->getOrder());

        return $steps;
    }
}
