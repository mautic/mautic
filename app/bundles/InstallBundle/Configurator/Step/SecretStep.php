<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\SecretStepType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Secret Step.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecretStep implements StepInterface
{
    /**
     * @Assert\NotBlank
     */
    public $secret;

    public function __construct(array $parameters)
    {
        if (array_key_exists('secret', $parameters)) {
            $this->secret = $parameters['secret'];

            if ('ThisTokenIsNotSoSecretChangeIt' == $this->secret) {
                $this->secret = $this->generateRandomSecret();
            }
        } else {
            $this->secret = $this->generateRandomSecret();
        }

    }

    private function generateRandomSecret()
    {
        return hash('sha1', uniqid(mt_rand()));
    }

    /**
     * @see StepInterface
     */
    public function getFormType()
    {
        return new SecretStepType();
    }

    /**
     * @see StepInterface
     */
    public function checkRequirements()
    {
        return array();
    }

    /**
     * checkOptionalSettings
     */
    public function checkOptionalSettings()
    {
        return array();
    }

    /**
     * @see StepInterface
     */
    public function update(StepInterface $data)
    {
        return array('secret' => $data->secret);
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Step:secret.html.php';
    }
}
