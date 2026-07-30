<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\CoreBundle\Update\Step\DeleteCacheStep;
use Mautic\CoreBundle\Update\Step\FinalizeUpdateStep;
use Mautic\CoreBundle\Update\Step\InstallNewFilesStep;
use Mautic\CoreBundle\Update\Step\PreUpdateChecksStep;
use Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep;
use Mautic\CoreBundle\Update\Step\UpdateSchemaStep;
use Mautic\CoreBundle\Update\Step\UpdateTranslationsStep;
use Mautic\CoreBundle\Update\StepProvider;
use Psr\Container\ContainerInterface;

final class UpdateStepSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * The steps run before the new files are in place, in the order they run in.
     *
     * @var string[]
     */
    private const EXPECTED_INITIAL_STEP_CLASSES = [
        PreUpdateChecksStep::class,
        InstallNewFilesStep::class,
        RemoveDeletedFilesStep::class,
        DeleteCacheStep::class,
    ];

    /**
     * The steps run once the new files are in place, in the order they run in.
     *
     * @var string[]
     */
    private const EXPECTED_FINAL_STEP_CLASSES = [
        UpdateTranslationsStep::class,
        UpdateSchemaStep::class,
        FinalizeUpdateStep::class,
    ];

    /**
     * A step is collected by the "mautic.update_step" tag alone, autoconfigured off StepInterface. A step
     * left out of the container is no error, the update simply skips that part of the work.
     */
    public function testUpdateStepsAreCollected(): void
    {
        $stepProvider = $this->resolveStepProvider();

        $this->assertSame(
            self::EXPECTED_INITIAL_STEP_CLASSES,
            array_map(fn (object $step): string => $step::class, $stepProvider->getInitialSteps())
        );

        $this->assertSame(
            self::EXPECTED_FINAL_STEP_CLASSES,
            array_map(fn (object $step): string => $step::class, $stepProvider->getFinalSteps())
        );
    }

    private function resolveStepProvider(): StepProvider
    {
        /** @var ContainerInterface $testContainer */
        $testContainer = $this->buildContainer()->get('test.service_container');

        $stepProvider = $testContainer->get(StepProvider::class);
        \assert($stepProvider instanceof StepProvider);

        return $stepProvider;
    }
}
