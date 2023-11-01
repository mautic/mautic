<?php

namespace Mautic\CoreBundle\Update;

use Mautic\CoreBundle\Update\Step\StepInterface;

class StepProvider
{
    private $initialSteps = [];
    private $finalSteps   = [];

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
        usort($steps, function (StepInterface $step1, StepInterface $step2) {
            if ($step1->getOrder() === $step2->getOrder()) {
                return 0;
            }

            return ($step1->getOrder() < $step2->getOrder()) ? -1 : 1;
        });

        return $steps;
    }
}
