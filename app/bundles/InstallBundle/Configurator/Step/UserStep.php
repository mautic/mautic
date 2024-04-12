<?php

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\UserStepType;

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

    public function getFormType(): string
    {
        return UserStepType::class;
    }

    public function checkRequirements(): array
    {
        return [];
    }

    public function checkOptionalSettings(): array
    {
        return [];
    }

    public function getTemplate(): string
    {
        return '@MauticInstall/Install/user.html.twig';
    }

    public function update(StepInterface $data): array
    {
        return [];
    }
}
