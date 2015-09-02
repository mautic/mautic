<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\InstallBundle\Configurator\Step\StepInterface;
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
     * Configuration filename
     *
     * @var string
     */
    protected $filename;

    /**
     * Array containing the steps
     *
     * @var StepInterface[]
     */
    protected $steps;

    /**
     * Configuration parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * Constructor.
     *
     * @param string $kernelDir
     * @param MauticFactory $factory
     */
    public function __construct($kernelDir, MauticFactory $factory)
    {
        $this->filename  = $factory->getLocalConfigFile(false);
        $this->steps      = array();
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
     */
    public function addStep(StepInterface $step)
    {
        //merge existing settings
        $properties = get_object_vars($step);

        $existing = $this->getParameters();
        foreach ($properties as $p => $v) {
            if (isset($existing[$p])) {
                $step->$p = $existing[$p];
            }
        }

        $this->steps[] = $step;
    }

    /**
     * Retrieves the specified step.
     *
     * @param integer $index
     *
     * @return StepInterface
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
     * Retrieves the loaded steps.
     *
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
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
     * @return integer
     */
    public function getStepCount()
    {
        return count($this->steps);
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
        $majors = array();
        foreach ($this->steps as $step) {
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
        $minors = array();
        foreach ($this->steps as $step) {
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
                    $value = "'" . addslashes($value) . "'";
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
            if (is_string($key)) {
                if ($counter === $count) {
                    $string .= str_repeat("\t", $level + 1);
                }
                $string .= '"'.$key.'" => ';
            }

            if (is_array($value)) {
                $string .= $this->renderArray($value, $level + 1);
            } else {
                $string .= '"'.addslashes($value).'"';
            }

            $counter--;
            if ($counter > 0) {
                $string .= ", \n" . str_repeat("\t", $level + 1);
            }
        }
        $string .= "\n" . str_repeat("\t", $level) . ")";

        return $string;
    }

    /**
     * Writes parameters to file.
     *
     * @return integer
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
            return array();
        }

        include $this->filename;

        // Return the $parameters array defined in the file
        return isset($parameters) ? $parameters : array();
    }
}
