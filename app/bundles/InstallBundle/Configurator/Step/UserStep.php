<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\UserStepType;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Step.
 */
class UserStep implements StepInterface
{
    /**
     * User's first name
     *
     * @Assert\NotBlank(message = "mautic.install.notblank")
     */
    public $firstname;

    /**
     * User's last name
     *
     * @Assert\NotBlank(message = "mautic.install.notblank")
     */
    public $lastname;

    /**
     * User's e-mail address
     *
     * @Assert\NotBlank(message = "mautic.install.notblank")
     * @Assert\Email(message = "mautic.install.invalidemail")
     */
    public $email;

    /**
     * User's username
     *
     * @Assert\NotBlank(message = "mautic.install.notblank")
     */
    public $username;

    /**
     * User's password
     *
     * @Assert\NotBlank(message = "mautic.install.notblank")
     * @Assert\Length(min = 6, minMessage = "mautic.install.password.minlength")
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
