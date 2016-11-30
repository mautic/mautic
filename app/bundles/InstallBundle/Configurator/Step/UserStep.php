<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\UserStepType;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * User Step.
 */
class UserStep implements StepInterface
{
    /**
     * User's first name.
     */
    public $firstname;

    /**
     * User's last name.
     */
    public $lastname;

    /**
     * User's e-mail address.
     */
    public $email;

    /**
     * User's username.
     */
    public $username;

    /**
     * User's password.
     */
    public $password;

    /**
     * @var Session
     */
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new UserStepType($this->session);
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings()
    {
        return [];
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
        return [];
    }
}
