<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use Mautic\CoreBundle\Helper\ExitCode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'mautic:phpunit:config',
    description: 'Outputs PHPUnit configuration with <testsuites> split into passed [numberOfSuites]'
)]
final class PhpUnitConfigCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('numberOfSuites', InputArgument::REQUIRED, 'Number of test suites')
            ->addOption('--slow-tests', null, InputOption::VALUE_REQUIRED, 'Path to a PHP file containing an array of slow tests with keys "slowTests" and "extraChunks"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfSuites = (int) $input->getArgument('numberOfSuites');
        $slowTestsPath  = $input->getOption('slow-tests');
        $functional     = [];
        $unit           = [];

        $finder = new Finder();
        $finder->files()->in([__DIR__.'/../../*/Tests', __DIR__.'/../../../../plugins/*/Tests'])->name('*Test.php');

        if (0 === $finder->count()) {
            return ExitCode::SUCCESS;
        }

        $slowTestsArray = $slowTestsPath ? include $slowTestsPath : [];
        $slowTests      = $slowTestsArray['slowTests'] ?? [];
        $extraChunks    = $slowTestsArray['extraChunks'] ?? [];

        foreach ($finder as $file) {
            if ($this->isFunctional($file)) {
                $functional[] = $file->getRealPath();
            } else {
                $unit[] = $file->getRealPath();
            }
        }

        $extraChunks      = $this->extraChunks($unit, $functional, $extraChunks);
        $numberOfChunks   = $numberOfSuites - count($extraChunks);
        $chunks           = $this->chunkTests($unit, $numberOfChunks);
        $functionalChunks = $this->chunkTests($functional, $numberOfChunks, $slowTests);
        $warmup           = realpath(__DIR__.'/../../CoreBundle/Test/FunctionalWarmupTest.php');

        foreach ($chunks as $index => $chunk) {
            $chunks[$index] = array_merge([$warmup], $functionalChunks[$index], $chunk);
        }

        $chunks = array_merge($chunks, $extraChunks);

        $output->write($this->renderConfig($chunks));

        return ExitCode::SUCCESS;
    }

    private function isFunctional(\SplFileInfo $file): bool
    {
        if (1 === preg_match('~/Functional/~', $file->getRealPath())) {
            return true;
        }

        if (is_subclass_of($this->getClassName($file->getRealPath()), MauticMysqlTestCase::class)) {
            return true;
        }

        return false;
    }

    private function getClassName(string $path): string
    {
        if (preg_match('~/plugins(/.+?)\.php$~', $path, $matches)) {
            return 'MauticPlugin'.str_replace('/', '\\', $matches[1]);
        }

        if (preg_match('~/app/bundles(/.+?)\.php$~', $path, $matches)) {
            return 'Mautic'.str_replace('/', '\\', $matches[1]);
        }

        throw new \InvalidArgumentException(sprintf('Unknown path: "%s"', $path));
    }

    /**
     * @param array<int, string[]> $chunks
     */
    private function renderConfig(array $chunks): string
    {
        $config = file_get_contents(__DIR__.'/../../../phpunit.xml.dist');

        return preg_replace('~<testsuites>(.*?)</testsuites>~s', $this->renderSuites($chunks), $config);
    }

    /**
     * @param array<int, string[]> $chunks
     */
    private function renderSuites(array $chunks): string
    {
        $output = '<testsuites>'.PHP_EOL;

        foreach ($chunks as $index => $chunk) {
            $output .= '<testsuite name="suite'.($index + 1).'">'.PHP_EOL;

            foreach ($chunk as $filename) {
                $output .= '    <file>'.$filename.'</file>'.PHP_EOL;
            }

            $output .= '</testsuite>'.PHP_EOL;
        }

        $output .= '</testsuites>'.PHP_EOL;

        return $output;
    }

    /**
     * @param string[]        $unit
     * @param string[]        $functional
     * @param array<string>[] $extraChunks
     *
     * @return array<string>[]
     */
    private function extraChunks(array &$unit, array &$functional, array $extraChunks): array
    {
        $chunks = [];

        foreach ($extraChunks as $extraChunkIndex => $extraChunk) {
            foreach ($extraChunk as $extraChunkTest) {
                foreach ($unit as $index => $unitFilename) {
                    if ($this->getClassName($unitFilename) === $extraChunkTest) {
                        if (!isset($chunks[$extraChunkIndex])) {
                            $chunks[$extraChunkIndex] = [];
                        }

                        $chunks[$extraChunkIndex][] = $unitFilename;
                        unset($unit[$index]);
                    }
                }
                foreach ($functional as $index => $functionalFilename) {
                    if ($this->getClassName($functionalFilename) === $extraChunkTest) {
                        if (!isset($chunks[$extraChunkIndex])) {
                            $chunks[$extraChunkIndex] = [];
                        }

                        $chunks[$extraChunkIndex][] = $functionalFilename;
                        unset($functional[$index]);
                    }
                }
            }
        }

        return $chunks;
    }

    /**
     * @param string[] $tests
     * @param string[] $slowTests
     *
     * @return array<string>[]
     */
    private function chunkTests(array $tests, int $numberOfChunks, array $slowTests = []): array
    {
        $extracted = [];

        // extract existing slow tests
        if ($slowTests) {
            foreach ($slowTests as $slowTest) {
                foreach ($tests as $index => $filename) {
                    if ($this->getClassName($filename) === $slowTest) {
                        $extracted[] = $filename;
                        unset($tests[$index]);
                    }
                }
            }
        }

        $extracted  = array_reverse($extracted);
        $chunks     = array_chunk($tests, (int) ceil(count($tests) / $numberOfChunks));
        $chunkIndex = 0;

        // spread slow tests
        while ($test = array_shift($extracted)) {
            array_unshift($chunks[$chunkIndex % $numberOfChunks], $test);
            ++$chunkIndex;
        }

        return $chunks;
    }
}
