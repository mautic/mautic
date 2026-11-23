<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

/**
 * Temporary per-step timing of test setUp/tearDown, printed to STDERR at the end of the run.
 */
final class StepProfiler
{
    /**
     * @var array<string, array{int, float}>
     */
    private static array $steps = [];

    private static bool $registered = false;

    public static function now(): float
    {
        return hrtime(true) / 1e9;
    }

    public static function add(string $step, float $start): void
    {
        $elapsed = self::now() - $start;

        self::$steps[$step] ??= [0, 0.0];
        ++self::$steps[$step][0];
        self::$steps[$step][1] += $elapsed;

        if (!self::$registered) {
            self::$registered = true;
            register_shutdown_function([self::class, 'printSummary']);
        }
    }

    public static function printSummary(): void
    {
        // child process of a test run in isolation - its STDERR would fail the test
        if (function_exists('__phpunit_run_isolated_test')) {
            return;
        }

        $lines = ['', 'STEP-PROFILE | step | calls | total s | avg ms |', 'STEP-PROFILE |---|---:|---:|---:|'];
        foreach (self::$steps as $step => [$calls, $total]) {
            $lines[] = sprintf('STEP-PROFILE | %s | %d | %.2f | %.2f |', $step, $calls, $total, $total / $calls * 1000);
        }

        fwrite(STDERR, implode(PHP_EOL, $lines).PHP_EOL);
    }
}
