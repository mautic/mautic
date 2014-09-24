<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\UserStepType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Step.
 */
class UserStep implements StepInterface
{
    /**
     * User's first name
     *
     * @Assert\NotBlank
     */
    public $firstname;

    /**
     * User's last name
     *
     * @Assert\NotBlank
     */
    public $lastname;

    /**
     * User's e-mail address
     *
     * @Assert\NotBlank
     * @Assert\Email
     */
    public $email;

    /**
     * User's username
     *
     * @Assert\NotBlank
     */
    public $username;

    /**
     * User's password
     *
     * @Assert\NotBlank
     */
    public $password;

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new UserStepType();
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Install:user.html.php';
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data)
    {
        return array();
    }
}
