<?php

namespace Mautic\CoreBundle\Configurator;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @note   This class is based on Sensio\Bundle\DistributionBundle\Configurator\Configurator
 */
class Configurator
{
    /**
     * Configuration filename.
     */
    protected string $filename;

    /**
     * Array containing the steps.
     *
     * @var array<int, StepInterface[]>
     */
    protected $steps = [];

    /**
     * Array containing the sorted steps.
     *
     * @var StepInterface[]
     */
    protected $sortedSteps = [];

    /**
     * Configuration parameters.
     *
     * @var array<string, mixed>
     */
    protected array $parameters;

    public function __construct(PathsHelper $pathsHelper)
    {
        $this->filename   = ParameterLoader::getLocalConfigFile($pathsHelper->getSystemPath('root').'/app');
        $this->parameters = $this->read();
    }

    /**
     * Check if the configuration path is writable.
     */
    public function isFileWritable(): bool
    {
        // If there's already a file, check it
        if (file_exists($this->filename)) {
            return is_writable($this->filename);
        }

        // If there isn't already, we check the parent folder
        return is_writable(dirname($this->filename));
    }

    /**
     * Add a step to the configurator.
     *
     * @param int $priority
     */
    public function addStep(StepInterface $step, $priority = 0): void
    {
        if (!isset($this->steps[$priority])) {
            $this->steps[$priority] = [];
        }

        $this->steps[$priority][] = $step;
        $this->sortedSteps        = [];
    }

    /**
     * Retrieves the specified step.
     *
     * @param int $index
     *
     * @return StepInterface[]
     *
     * @throws \InvalidArgumentException
     */
    public function getStep($index)
    {
        if (isset($this->steps[$index])) {
            return $this->steps[$index];
        }

        throw new \InvalidArgumentException(sprintf('There is not a step %s', $index));
    }

    /**
     * Retrieves the loaded steps in sorted order.
     *
     * @return StepInterface[]
     */
    public function getSteps()
    {
        if ([] === $this->sortedSteps) {
            $this->sortedSteps = $this->getSortedSteps();
        }

        return $this->sortedSteps;
    }

    /**
     * Sort routers by priority.
     * The highest priority number is the highest priority (reverse sorting).
     *
     * @return StepInterface[]
     */
    private function getSortedSteps(): array
    {
        $sortedSteps = [];
        krsort($this->steps);

        foreach ($this->steps as $steps) {
            $sortedSteps = array_merge($sortedSteps, $steps);
        }

        return $sortedSteps;
    }

    /**
     * Retrieves the configuration parameters.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Return the number of steps in the configurator.
     */
    public function getStepCount(): int
    {
        return count($this->getSteps());
    }

    /**
     * Merges parameters to the main configuration.
     *
     * @param array<string, mixed> $parameters
     */
    public function mergeParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * Fetches the requirements from the defined steps.
     *
     * @return array<string>
     */
    public function getRequirements(): array
    {
        $majors = [];

        foreach ($this->getSteps() as $step) {
            foreach ($step->checkRequirements() as $major) {
                $majors[] = $major;
            }
        }

        return $majors;
    }

    /**
     * Fetches the optional settings from the defined steps.
     *
     * @return array<string>
     */
    public function getOptionalSettings(): array
    {
        $minors = [];

        foreach ($this->getSteps() as $step) {
            foreach ($step->checkOptionalSettings() as $minor) {
                $minors[] = $minor;
            }
        }

        return $minors;
    }

    /**
     * Renders parameters as a string.
     */
    public function render(): string
    {
        $string = "<?php\n";
        $string .= "\$parameters = array(\n";

        foreach ($this->parameters as $key => $value) {
            if ('' !== $value) {
                if (is_string($value)) {
                    $value = "'".addcslashes($value, '\\\'')."'";
                } elseif (is_bool($value)) {
                    $value = ($value) ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = 'null';
                } elseif (is_array($value)) {
                    $value = $this->renderArray($value);
                }

                $string .= "\t'$key' => $value,\n";
            }
        }

        return $string.");\n";
    }

    /**
     * @param array<mixed> $array
     * @param int          $level
     */
    protected function renderArray($array, $level = 1): string
    {
        $string = "array(\n";

        $count = $counter = count($array);
        foreach ($array as $key => $value) {
            if ($counter === $count) {
                $string .= str_repeat("\t", $level + 1);
            }
            $string .= '\''.$key.'\' => ';

            if (is_array($value)) {
                $string .= $this->renderArray($value, $level + 1);
            } else {
                $string .= '\''.addcslashes((string) $value, '\\\'').'\'';
            }

            --$counter;
            if ($counter > 0) {
                $string .= ",\n".str_repeat("\t", $level + 1);
            }
        }

        return $string.("\n".str_repeat("\t", $level).')');
    }

    /**
     * Writes parameters to file.
     *
     * @throws RuntimeException
     */
    public function write(): int
    {
        if (!$this->isFileWritable()) {
            throw new RuntimeException('Cannot write the config file, the destination is unwritable.');
        }

        $return = file_put_contents($this->filename, $this->render());

        if (false === $return) {
            throw new RuntimeException('An error occurred while attempting to write the config file to the filesystem.');
        }

        return $return;
    }

    /**
     * Reads parameters from file.
     *
     * @return array<string, mixed>
     */
    protected function read(): array
    {
        if (!file_exists($this->filename)) {
            return [];
        }

        $parameters = [];
        include $this->filename;

        // Return the $parameters array defined in the file
        return $parameters;
    }
}
