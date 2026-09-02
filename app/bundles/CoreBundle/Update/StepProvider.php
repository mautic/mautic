<?php

namespace Mautic\CoreBundle\Update;

use Mautic\CoreBundle\Update\Step\StepInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class StepProvider
{
    /**
     * @var StepInterface[]
     */
    private array $initialSteps = [];

    /**
     * @var StepInterface[]
     */
    private array $finalSteps = [];

    /**
     * @param iterable<StepInterface> $steps
     */
    public function __construct(
        #[AutowireIterator('mautic.update_step')]
        iterable $steps = [],
    ) {
        foreach ($steps as $step) {
            $this->addStep($step);
        }
    }

    private function addStep(StepInterface $step): void
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
     * @param StepInterface[] $steps
     *
     * @return StepInterface[]
     */
    private function orderSteps(array $steps): array
    {
        usort($steps, fn (StepInterface $step1, StepInterface $step2): int => $step1->getOrder() <=> $step2->getOrder());

        return $steps;
    }
}
