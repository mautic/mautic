<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Configurator;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Configurator.
 *
 * @author Marc Weistroff <marc.weistroff@gmail.com>
 * @note   This class is based on Sensio\Bundle\DistributionBundle\Configurator\Configurator
 */
class Configurator
{
    /**
     * Configuration filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Array containing the steps.
     *
     * @var StepInterface[]
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
     * @var array
     */
    protected $parameters;

    /**
     * Configurator constructor.
     *
     * @param PathsHelper $pathsHelper
     */
    public function __construct(PathsHelper $pathsHelper)
    {
        $this->filename   = $pathsHelper->getSystemPath('local_config');
        $this->parameters = $this->read();
    }

    /**
     * Check if the configuration path is writable.
     *
     * @return bool
     */
    public function isFileWritable()
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
     * @param StepInterface $step
     * @param int           $priority
     */
    public function addStep(StepInterface $step, $priority = 0)
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
     * @return StepInterface
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
     * @return array
     */
    public function getSteps()
    {
        if ($this->sortedSteps === []) {
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
    private function getSortedSteps()
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
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return the number of steps in the configurator.
     *
     * @return int
     */
    public function getStepCount()
    {
        return count($this->getSteps());
    }

    /**
     * Merges parameters to the main configuration.
     *
     * @param array $parameters
     */
    public function mergeParameters($parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * Fetches the requirements from the defined steps.
     *
     * @return array
     */
    public function getRequirements()
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
     * @return array
     */
    public function getOptionalSettings()
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
     *
     * @return string
     */
    public function render()
    {
        $string = "<?php\n";
        $string .= "\$parameters = array(\n";

        foreach ($this->parameters as $key => $value) {
            if ($value !== '') {
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

        $string .= ");\n";

        return $string;
    }

    /**
     * @param     $array
     * @param int $level
     *
     * @return string
     */
    protected function renderArray($array, $level = 1)
    {
        $string = "array(\n";

        $count = $counter = count($array);
        foreach ($array as $key => $value) {
            if (is_string($key) or is_numeric($key)) {
                if ($counter === $count) {
                    $string .= str_repeat("\t", $level + 1);
                }
                $string .= '\''.$key.'\' => ';
            }

            if (is_array($value)) {
                $string .= $this->renderArray($value, $level + 1);
            } else {
                $string .= '\''.addcslashes($value, '\\\'').'\'';
            }

            --$counter;
            if ($counter > 0) {
                $string .= ",\n".str_repeat("\t", $level + 1);
            }
        }
        $string .= "\n".str_repeat("\t", $level).')';

        return $string;
    }

    /**
     * Writes parameters to file.
     *
     * @return int
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function write()
    {
        if (!$this->isFileWritable()) {
            throw new RuntimeException('Cannot write the config file, the destination is unwritable.');
        }

        $return = file_put_contents($this->filename, $this->render());

        if ($return === false) {
            throw new RuntimeException('An error occurred while attempting to write the config file to the filesystem.');
        }

        return $return;
    }

    /**
     * Reads parameters from file.
     *
     * @return array
     */
    protected function read()
    {
        if (!file_exists($this->filename)) {
            return [];
        }

        include $this->filename;

        // Return the $parameters array defined in the file
        return isset($parameters) ? $parameters : [];
    }
}
